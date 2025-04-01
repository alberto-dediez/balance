<?php

namespace App\Security\Application\User\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\UserRepository;

class RemoveUserService
{
    public function __construct(private UserRepository $repository, private UserAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveUserRequest $request, User $performBy): RemoveUserResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $this->authorizer->denyUnlessCanDoThat($user, Action::Remove, $performBy);

        $user->remove();

        $this->repository->add($user);

        return new RemoveUserResponse(
            userId: $user->id()
        );
    }
}