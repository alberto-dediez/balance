<?php

namespace App\Security\Application\ListUsers;

use App\Security\Domain\Model\User\UserRepository;

class ListUsersService
{
    public function __construct(private UserRepository $repository)
    {

    }

    public function __invoke(ListUsersRequest $request): ListUsersResponse
    {
        $users = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListUsersResponse($users, $totalResults);
    }
}