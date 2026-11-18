<?php

declare(strict_types=1);

namespace App\Security\Application\User\View;

use App\Security\Application\Action;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;

class ViewUserService
{
    public function __construct(private readonly UserRepository $repository, private readonly UserAuthorizer $authorizer) {}

    public function __invoke(ViewUserRequest $request, User $performBy): ViewUserResponse
    {
        $user = $this->repository->find($request->userId);

        if (null === $user) {
            throw new \InvalidArgumentException('User not found');
        }

        $this->authorizer->denyUnlessCanDoThat($user, Action::View, $performBy);

        return new ViewUserResponse($user);
    }
}
