<?php

namespace App\Security\Application\User\RecoverToken;

use InvalidArgumentException;
use App\Security\Domain\Model\User\UserRepository;

class RecoverTokenService
{
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
}