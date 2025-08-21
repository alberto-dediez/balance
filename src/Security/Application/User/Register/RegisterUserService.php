<?php

namespace App\Security\Application\User\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Security\Application\User\Register\PasswordHasher;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RegisterUserService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private UserRepository $userRepository, private PasswordHasher $passwordHasher, private UserAuthorizer $authorizer)
    {
    }

    public function __invoke(RegisterUserRequest $request, User $performBy): RegisterUserResponse
    {
        $user = new User(
            id: UserId::generate(),
            accountId: $request->accountId
        );

        $this->authorizer->denyUnlessCanDoThat($user, Action::Register, $performBy);

        if (null !== $this->userRepository->findByUsername($request->username)) {
            throw new DuplicateUsernameException($request->username);
        }

        $id = UserId::generate();

        $hashedPassword = $this->passwordHasher->hashPassword($request->password);

        $user = new User($id);
        $user->register($request->username, $hashedPassword, $request->accountId);

        $this->userRepository->add($user);

        return new RegisterUserResponse($user->id(), $user->username(), $user->accountId());
    }
    
    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterUserRequest;
    }
}