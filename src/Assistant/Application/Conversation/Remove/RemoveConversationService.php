<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Remove;

use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\Conversation\ConversationAttachmentStorage;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Clock\Clock;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveConversationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ConversationRepository $conversations,
        private ActionPlanRepository $plans,
        private ConversationAuthorizer $authorizer,
        private ConversationAttachmentStorage $attachmentStorage,
        private Clock $clock,
    ) {}

    public function __invoke(RemoveConversationRequest $request, User $user): RemoveConversationResponse
    {
        $conversation = $this->conversations->find($request->conversationId);

        if (null === $conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($conversation, Action::Remove, $user);

        if ($this->plans->totalResultsByFilter(new ActionPlanFilter(conversationId: $conversation->id(), statuses: ActionPlanStatus::active(), limit: 0)) > 0) {
            throw new ConversationBusyException();
        }

        $conversation->remove($this->clock->now());

        foreach ($this->plans->findByFilter(new ActionPlanFilter(conversationId: $conversation->id(), limit: 0)) as $plan) {
            $this->plans->remove($plan);
        }

        $this->conversations->remove($conversation);
        $this->attachmentStorage->removeOf($conversation->id());

        return new RemoveConversationResponse($conversation->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveConversationRequest;
    }
}
