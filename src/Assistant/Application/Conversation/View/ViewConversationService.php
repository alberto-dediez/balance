<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\View;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Application\Conversation\ConversationFingerprint;
use App\Assistant\Application\Engine\DailyBudget;
use App\Assistant\Application\Engine\Toolbox;
use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Clock\Clock;

class ViewConversationService
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly ActionPlanRepository $plans,
        private readonly ConversationAuthorizer $authorizer,
        private readonly Toolbox $toolbox,
        private readonly DailyBudget $budget,
        private readonly AssistantSettings $settings,
        private readonly Clock $clock,
    ) {}

    public function __invoke(ViewConversationRequest $request, User $user): ViewConversationResponse
    {
        $conversation = $this->conversations->find($request->conversationId);

        if (null === $conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($conversation, Action::View, $user);

        $plans = [];

        foreach ($this->plans->findByFilter(new ActionPlanFilter(conversationId: $conversation->id(), limit: 0)) as $plan) {
            $plans[(string) $plan->id()] = $plan;
        }

        $busy = $conversation->isBusy() || [] !== array_filter($plans, static fn(ActionPlan $plan): bool => $plan->isActive());
        $now = $this->clock->now();
        $entries = [];

        foreach ($conversation->messages() as $message) {
            $plan = null === $message->planId() ? null : ($plans[(string) $message->planId()] ?? null);

            $entries[] = new ThreadEntry(
                $message,
                $plan,
                null !== $plan && $plan->isProposed() && !$plan->isExpiredAt($now, $this->settings->planTtlMinutes) && !$busy,
            );
        }

        return new ViewConversationResponse(
            conversation: $conversation,
            entries: $entries,
            busy: $busy,
            spentToday: $this->budget->spentToday($user),
            dailyLimit: $this->settings->hasDailyLimit() ? $this->budget->limit() : '0',
            toolLabels: $this->toolbox->labels(),
            parameterLabels: $this->toolbox->parameterLabels(),
            fingerprint: ConversationFingerprint::of($conversation, $plans),
        );
    }
}
