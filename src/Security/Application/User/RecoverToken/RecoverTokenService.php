<?php

namespace App\Security\Application\User\RecoverToken;

use InvalidArgumentException;
use App\Shared\Application\CommandBus\Command;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RecoverTokenService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;
    
    public function __construct(private UserRepository $repository)
    {

    }

    public function __invoke(RecoverTokenRequest $request): void
    {
        $user = $this->repository->findByUsername($request->username);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $user->initPasswordRecovery();
        
        $this->repository->add($user);
    }
    
    public function isHandled(Command $command): bool
    {
        return $command instanceof RecoverTokenRequest;
    }
}