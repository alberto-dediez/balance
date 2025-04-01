<?php

namespace App\Security\Application\User\GrantRole;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\UserRepository;

class GrantRoleService
{
    public function __construct(private UserRepository $repository, private UserAuthorizer $authorizer)
    {

    }

    public function __invoke(GrantRoleRequest $request, User $performBy): GrantRoleResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $this->authorizer->denyUnlessCanDoThat($user, Action::Admin, $performBy);

        $user->grantRole($request->role);

        $this->repository->add($user);

        return new GrantRoleResponse(
            userId: $user->id(),
            username: $user->username(),
            roles: $user->roles()
        );
    }
}