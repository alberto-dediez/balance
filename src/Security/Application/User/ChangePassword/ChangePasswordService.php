<?php

namespace App\Security\Application\User\ChangePassword;

use InvalidArgumentException;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Application\User\Register\PasswordHasher;

class ChangePasswordService
{
    public function __construct(private UserRepository $repository, private PasswordHasher $passwordHasher)
    {

    }

    public function __invoke(ChangePasswordRequest $request): ChangePasswordResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $hashedPassword = $this->passwordHasher->hashPassword($request->password);

        $user->changePassword($hashedPassword);
        
        $this->repository->add($user);

        return new ChangePasswordResponse(
            userId: $user->id()
        );
    }
}