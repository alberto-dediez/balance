<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\ExpireStale;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanConcurrentlyModifiedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationFilter;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Live\LiveUpdates;
use Psr\Log\LoggerInterface;

class ExpireStaleAssistantWorkService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public const string INTERRUPTED_MESSAGE = 'La respuesta se ha interrumpido. Vuelve a intentarlo.';
    public const string INTERRUPTED_PLAN = 'La ejecución se ha interrumpido. Revisa los pasos realizados.';
    public const string LIVE_KEY = 'assistant';

    public function __construct(
        private ConversationRepository $conversations,
        private ActionPlanRepository $plans,
        private ConversationAuthorizer $authorizer,
        private AssistantSettings $settings,
        private LiveUpdates $liveUpdates,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ExpireStaleAssistantWorkRequest $request, User $user): ExpireStaleAssistantWorkResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Conversation(ConversationId::generate()), Action::Admin, $user);

        $staleLimit = $request->now->modify(sprintf('-%d minutes', $this->settings->staleMinutes));
        $ttlLimit = $request->now->modify(sprintf('-%d minutes', $this->settings->planTtlMinutes));

        /** @var array<string, UserId> $touched */
        $touched = [];
        $interrupted = 0;
        $failed = 0;
        $expired = 0;

        try {
            foreach ($this->plans->findByFilter(new ActionPlanFilter(statuses: ActionPlanStatus::active(), lastProgressBefore: $staleLimit, limit: 0)) as $plan) {
                if (!in_array($this->plans->statusOf($plan->id()), ActionPlanStatus::active(), true)) {
                    continue;
                }

                $plan->interrupt(self::INTERRUPTED_PLAN, $request->now);
                $this->plans->add($plan);
                ++$interrupted;

                $conversation = $this->conversations->find($plan->conversationId());
                $pending = $conversation?->pendingMessage();

                if (null !== $conversation && null !== $pending) {
                    $conversation->failMessage($pending->id(), self::INTERRUPTED_PLAN, AssistantUsage::none(), $request->now);
                    $this->conversations->add($conversation);
                    ++$failed;
                }

                if (null !== $plan->userId()) {
                    $touched[(string) $plan->userId()] = $plan->userId();
                }
            }

            foreach ($this->conversations->findByFilter(new ConversationFilter(pendingMessageBefore: $staleLimit, limit: 0)) as $conversation) {
                $pending = $conversation->pendingMessage();

                if (null === $pending) {
                    continue;
                }

                $conversation->failMessage($pending->id(), self::INTERRUPTED_MESSAGE, AssistantUsage::none(), $request->now);
                $this->conversations->add($conversation);
                ++$failed;

                if (null !== $conversation->userId()) {
                    $touched[(string) $conversation->userId()] = $conversation->userId();
                }
            }

            foreach ($this->plans->findByFilter(new ActionPlanFilter(statuses: [ActionPlanStatus::Proposed], proposedBefore: $ttlLimit, limit: 0)) as $plan) {
                if (ActionPlanStatus::Proposed !== $this->plans->statusOf($plan->id())) {
                    continue;
                }

                $plan->expire($request->now);
                $this->plans->add($plan);
                ++$expired;

                if (null !== $plan->userId()) {
                    $touched[(string) $plan->userId()] = $plan->userId();
                }
            }
        } catch (ActionPlanConcurrentlyModifiedException $exception) {
            $this->logger->warning('The assistant stale work pass stopped on a plan modified concurrently', ['exception' => $exception]);
        }

        foreach ($touched as $userId) {
            $this->liveUpdates->touchedForUser($userId, self::LIVE_KEY);
        }

        return new ExpireStaleAssistantWorkResponse($failed, $interrupted, $expired);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ExpireStaleAssistantWorkRequest;
    }
}
