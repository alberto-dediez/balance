<?php

declare(strict_types=1);

namespace App\Security\Application\User\UngrantRole;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UngrantRoleService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private UserRepository $repository, private UserAuthorizer $authorizer) {}

    public function __invoke(UngrantRoleRequest $request, User $performBy): UngrantRoleResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new \InvalidArgumentException('User not found');
        }

        if (!$this->authorizer->canChangeRole($user, $request->role, $performBy)) {
            throw new UnauthorizedActionException($user, Action::Admin);
        }

        $user->ungrantRole($request->role);

        $this->repository->add($user);

        return new UngrantRoleResponse(
            userId: $user->id(),
            username: $user->username(),
            roles: $user->roles(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UngrantRoleRequest;
    }
}
