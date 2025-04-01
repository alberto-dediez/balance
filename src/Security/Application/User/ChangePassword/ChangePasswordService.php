<?php

namespace App\Security\Application\User\ChangePassword;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Application\User\Register\PasswordHasher;

class ChangePasswordService
{
    public function __construct(private UserRepository $repository, private PasswordHasher $passwordHasher, private UserAuthorizer $authorizer)
    {

    }

    public function __invoke(ChangePasswordRequest $request, User $performBy): ChangePasswordResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $this->authorizer->denyUnlessCanDoThat($user, Action::Edit, $performBy);

        $hashedPassword = $this->passwordHasher->hashPassword($request->password);

        $user->changePassword($hashedPassword);
        
        $this->repository->add($user);

        return new ChangePasswordResponse(
            userId: $user->id()
        );
    }
}