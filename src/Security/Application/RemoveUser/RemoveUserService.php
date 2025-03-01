<?php

namespace App\Security\Application\RemoveUser;

use App\Security\Domain\Model\User\UserRepository;
use InvalidArgumentException;

class RemoveUserService
{
    public function __construct(private UserRepository $repository)
    {

    }

    public function __invoke(RemoveUserRequest $request): RemoveUserResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $user->remove();

        $this->repository->add($user);

        return new RemoveUserResponse(
            userId: $user->id()
        );
    }
}