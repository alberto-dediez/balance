<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Purge;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationAttachmentStorage;
use App\Assistant\Domain\Model\Conversation\ConversationFilter;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class PurgeConversationsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ConversationRepository $conversations,
        private ActionPlanRepository $plans,
        private ConversationAuthorizer $authorizer,
        private AssistantSettings $settings,
        private ConversationAttachmentStorage $attachmentStorage,
    ) {}

    public function __invoke(PurgeConversationsRequest $request, User $user): PurgeConversationsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Conversation(ConversationId::generate()), Action::Admin, $user);

        $limit = $request->now->modify(sprintf('-%d days', $this->settings->retentionDays));
        $purged = 0;

        foreach ($this->conversations->findByFilter(new ConversationFilter(lastActivityBefore: $limit, limit: 0)) as $conversation) {
            foreach ($this->plans->findByFilter(new ActionPlanFilter(conversationId: $conversation->id(), limit: 0)) as $plan) {
                $this->plans->remove($plan);
            }

            $this->conversations->remove($conversation);
            $this->attachmentStorage->removeOf($conversation->id());
            ++$purged;
        }

        return new PurgeConversationsResponse($purged);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof PurgeConversationsRequest;
    }
}
