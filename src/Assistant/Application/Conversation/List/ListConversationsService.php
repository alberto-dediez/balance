<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\List;

use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ListConversationsService
{
    public function __construct(
        private readonly ConversationRepository $repository,
        private readonly ConversationAuthorizer $authorizer,
    ) {}

    public function __invoke(ListConversationsRequest $request, User $user): ListConversationsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Conversation(ConversationId::generate()), Action::List, $user);

        $request->userId = $user->id();

        return new ListConversationsResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
