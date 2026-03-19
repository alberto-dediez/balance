<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeferredMessageHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LoggedUser $loggedUser,
    ) {}

    public function __invoke(DeferredMessage $message): void
    {
        $user = $this->userRepository->find($message->userId());

        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User not found');
        }

        $this->loggedUser->forceUser($user);

        CommandBusDispatcher::instance()->dispatch(
            $message->command(),
            $user,
        );
    }
}
