<?php

declare(strict_types=1);

namespace App\Security\Application\User\RecoverPassword;

use App\Security\Application\User\Register\PasswordHasher;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RecoverPasswordService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private UserRepository $repository, private PasswordHasher $passwordHasher) {}

    public function __invoke(RecoverPasswordRequest $request, ?User $user = null): RecoverPasswordResponse
    {
        $user = $this->repository->findByRecoverToken($request->token);

        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User not found');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($request->password);

        $user->recoverPassword($request->token, $hashedPassword);

        $this->repository->add($user);

        return new RecoverPasswordResponse(
            userId: $user->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RecoverPasswordRequest;
    }
}
