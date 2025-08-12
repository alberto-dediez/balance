<?php

namespace App\Security\Application\User\RecoverPassword;

use InvalidArgumentException;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Application\User\Register\PasswordHasher;

class RecoverPasswordService
{
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
}