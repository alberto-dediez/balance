<?php

namespace App\Security\Application\User\UngrantRole;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\UserRepository;

class UngrantRoleService
{
    public function __construct(private UserRepository $repository, private UserAuthorizer $authorizer)
    {

    }

    public function __invoke(UngrantRoleRequest $request, User $performBy): UngrantRoleResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $this->authorizer->denyUnlessCanDoThat($user, Action::Admin, $performBy);

        $user->ungrantRole($request->role);
        
        $this->repository->add($user);

        return new UngrantRoleResponse(
            userId: $user->id(),
            username: $user->username(),
            roles: $user->roles()
        );
    }
}