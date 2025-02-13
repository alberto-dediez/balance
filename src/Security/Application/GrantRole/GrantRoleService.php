<?php

namespace App\Security\Application\GrantRole;

use App\Security\Domain\Model\User\UserRepository;
use InvalidArgumentException;

class GrantRoleService
{
    public function __construct(private UserRepository $repository)
    {

    }

    public function __invoke(GrantRoleRequest $request): GrantRoleResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $user->grantRole($request->role);

        $this->repository->add($user);

        return new GrantRoleResponse(
            userId: $user->id(),
            username: $user->username(),
            roles: $user->roles()
        );
    }
}