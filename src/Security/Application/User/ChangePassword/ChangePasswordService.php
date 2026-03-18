<?php

declare(strict_types=1);

namespace App\Security\Application\User\ChangePassword;

use App\Security\Application\Action;
use App\Security\Application\User\Register\PasswordHasher;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ChangePasswordService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private UserRepository $repository, private PasswordHasher $passwordHasher, private UserAuthorizer $authorizer) {}

    public function __invoke(ChangePasswordRequest $request, User $performBy): ChangePasswordResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new \InvalidArgumentException('User not found');
        }

        $this->authorizer->denyUnlessCanDoThat($user, Action::Edit, $performBy);

        $hashedPassword = $this->passwordHasher->hashPassword($request->password);

        $user->changePassword($hashedPassword);

        $this->repository->add($user);

        return new ChangePasswordResponse(
            userId: $user->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ChangePasswordRequest;
    }
}
