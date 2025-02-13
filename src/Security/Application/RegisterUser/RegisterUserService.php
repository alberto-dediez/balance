<?php

namespace App\Security\Application\RegisterUser;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Application\RegisterUser\PasswordHasher;

class RegisterUserService
{
    public function __construct(private UserRepository $userRepository, private PasswordHasher $passwordHasher)
    {
    }

    public function __invoke(RegisterUserRequest $request): RegisterUserResponse
    {
        if (null !== $this->userRepository->findByUsername($request->username)) {
            throw new DuplicateUsernameException($request->username);
        }

        $id = UserId::generate();

        $hashedPassword = $this->passwordHasher->hashPassword($request->password);

        $user = new User($id);
        $user->register($request->username, $hashedPassword);

        $this->userRepository->add($user);

        return new RegisterUserResponse($user->id(), $user->username());
    }
}