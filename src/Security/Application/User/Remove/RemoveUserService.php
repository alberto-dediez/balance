<?php

namespace App\Security\Application\User\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RemoveUserService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private UserRepository $repository, private UserAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveUserRequest $request, User $performBy): RemoveUserResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $this->authorizer->denyUnlessCanDoThat($user, Action::Remove, $performBy);

        $user->remove();

        $this->repository->add($user);

        return new RemoveUserResponse(
            userId: $user->id()
        );
    }
    
    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveUserRequest;
    }
}