<?php

namespace App\Security\Application\User\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\UserRepository;

class ListUsersService
{
    public function __construct(private UserRepository $repository, private UserAuthorizer $authorizer)
    {

    }

    public function __invoke(ListUsersRequest $request, User $performBy): ListUsersResponse
    {
        $user = new User(
            id: UserId::generate(),
            accountId: $request->account?->id()
        );

        $this->authorizer->denyUnlessCanDoThat($user, Action::List, $performBy);

        $users = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListUsersResponse($users, $totalResults);
    }
}