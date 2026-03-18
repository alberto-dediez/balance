<?php

declare(strict_types=1);

namespace App\Security\Application\User\RecoverToken;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RecoverTokenService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private UserRepository $repository) {}

    public function __invoke(RecoverTokenRequest $request, ?User $user = null): null
    {
        $user = $this->repository->findByUsername($request->username);

        if (null == $user) {
            throw new \InvalidArgumentException('User not found');
        }

        $user->initPasswordRecovery();

        $this->repository->add($user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RecoverTokenRequest;
    }
}
