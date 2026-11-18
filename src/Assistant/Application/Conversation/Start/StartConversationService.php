<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Start;

use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Clock\Clock;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class StartConversationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ConversationRepository $repository,
        private ConversationAuthorizer $authorizer,
        private Clock $clock,
    ) {}

    public function __invoke(StartConversationRequest $request, User $user): StartConversationResponse
    {
        $conversation = new Conversation(ConversationId::generate());

        $this->authorizer->denyUnlessCanDoThat($conversation, Action::Register, $user);

        $conversation->start($user->id(), $this->clock->now());

        $this->repository->add($conversation);

        return new StartConversationResponse($conversation->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof StartConversationRequest;
    }
}
