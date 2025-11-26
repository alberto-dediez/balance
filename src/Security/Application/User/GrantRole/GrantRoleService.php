<?php

namespace App\Security\Application\User\GrantRole;

use App\Security\Application\UnauthorizedActionException;
use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class GrantRoleService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private UserRepository $repository, private UserAuthorizer $authorizer)
    {

    }

    public function __invoke(GrantRoleRequest $request, User $performBy): GrantRoleResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        if (!$this->authorizer->canChangeRole($user, $request->role, $performBy)) {
            throw new UnauthorizedActionException($user, Action::Admin);
        }

        $user->grantRole($request->role);

        $this->repository->add($user);

        return new GrantRoleResponse(
            userId: $user->id(),
            username: $user->username(),
            roles: $user->roles()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GrantRoleRequest;
    }
}
