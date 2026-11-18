<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Stop;

use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanConcurrentlyModifiedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\ActionPlan\PlanNotExecutableException;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Domain\Model\Conversation\MessageStatus;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Clock\Clock;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Live\LiveUpdates;
use App\Shared\Domain\Event\DomainEventPublisher;

class StopConversationWorkService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public const string STOPPED_MESSAGE = 'Respuesta detenida';
    public const string STOPPED_PLAN = 'Ejecución detenida. Revisa los pasos realizados.';
    public const string LIVE_KEY = 'assistant';

    public function __construct(
        private ConversationRepository $conversations,
        private ActionPlanRepository $plans,
        private ConversationAuthorizer $authorizer,
        private LiveUpdates $liveUpdates,
        private Clock $clock,
    ) {}

    public function __invoke(StopConversationWorkRequest $request, User $user): StopConversationWorkResponse
    {
        $conversation = $this->conversations->find($request->conversationId);

        if (null === $conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($conversation, Action::Edit, $user);

        $pending = $conversation->pendingMessage();

        if (null === $pending) {
            return new StopConversationWorkResponse(false);
        }

        $now = $this->clock->now();

        foreach ($this->plans->findByFilter(new ActionPlanFilter(conversationId: $conversation->id(), statuses: ActionPlanStatus::active(), limit: 0)) as $plan) {
            try {
                $plan->interrupt(self::STOPPED_PLAN, $now);
                $this->plans->add($plan);
            } catch (ActionPlanConcurrentlyModifiedException) {
                DomainEventPublisher::instance()->clearPending();

                return new StopConversationWorkResponse(false);
            } catch (PlanNotExecutableException) {
                continue;
            }
        }

        if (MessageStatus::Pending !== $this->conversations->messageStatusOf($pending->id())) {
            return new StopConversationWorkResponse(false);
        }

        $reason = MessageKind::Execution === $pending->kind() ? self::STOPPED_PLAN : self::STOPPED_MESSAGE;

        $conversation->failMessage($pending->id(), $reason, AssistantUsage::none(), $now);
        $this->conversations->add($conversation);

        $this->liveUpdates->touchedForUser($user->id(), self::LIVE_KEY);

        return new StopConversationWorkResponse(true);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof StopConversationWorkRequest;
    }
}
