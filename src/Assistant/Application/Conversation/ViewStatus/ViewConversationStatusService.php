<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\ViewStatus;

use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Application\Conversation\ConversationFingerprint;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ViewConversationStatusService
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly ActionPlanRepository $plans,
        private readonly ConversationAuthorizer $authorizer,
    ) {}

    public function __invoke(ViewConversationStatusRequest $request, User $user): ViewConversationStatusResponse
    {
        $conversation = $this->conversations->find($request->conversationId);

        if (null === $conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($conversation, Action::View, $user);

        $plans = $this->plans->findByFilter(new ActionPlanFilter(conversationId: $conversation->id(), limit: 0));
        $busy = $conversation->isBusy();

        foreach ($plans as $plan) {
            $busy = $busy || $plan->isActive();
        }

        return new ViewConversationStatusResponse(ConversationFingerprint::of($conversation, $plans), $busy);
    }
}
