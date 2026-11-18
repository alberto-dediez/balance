<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Rename;

use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Clock\Clock;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RenameConversationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ConversationRepository $conversations,
        private ConversationAuthorizer $authorizer,
        private Clock $clock,
    ) {}

    public function __invoke(RenameConversationRequest $request, User $user): RenameConversationResponse
    {
        $conversation = $this->conversations->find($request->conversationId);

        if (null === $conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($conversation, Action::Edit, $user);

        $conversation->rename($request->title, $this->clock->now());

        $this->conversations->add($conversation);

        return new RenameConversationResponse($conversation->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RenameConversationRequest;
    }
}
