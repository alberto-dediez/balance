<?php

namespace App\Security\Application\User\RecoverPassword;

use InvalidArgumentException;
use App\Shared\Application\CommandBus\Command;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Security\Application\User\Register\PasswordHasher;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RecoverPasswordService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private UserRepository $repository, private PasswordHasher $passwordHasher)
    {

    }

    public function __invoke(RecoverPasswordRequest $request): RecoverPasswordResponse
    {
        $user = $this->repository->findByRecoverToken($request->token);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $hashedPassword = $this->passwordHasher->hashPassword($request->password);

        $user->recoverPassword($request->token, $hashedPassword);
        
        $this->repository->add($user);

        return new RecoverPasswordResponse(
            userId: $user->id()
        );
    }
    
    public function isHandled(Command $command): bool
    {
        return $command instanceof RecoverPasswordRequest;
    }
}