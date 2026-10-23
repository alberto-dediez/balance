<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\TimedCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeferredMessageHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LoggedUser $loggedUser,
        private readonly ImpersonatedAccount $impersonatedAccount,
        private readonly LoggedAccount $loggedAccount,
    ) {}

    public function __invoke(DeferredMessage $message): void
    {
        $user = $this->userRepository->find($message->userId());

        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User not found');
        }

        $this->loggedUser->forceUser($user);
        $this->impersonatedAccount->force($message->impersonatedAccountId(), $user->id());
        $this->loggedAccount->reset();

        try {
            CommandBusDispatcher::instance()->dispatch(
                $this->commandAtExecutionTime($message->command()),
                $user,
            );
        } finally {
            $this->loggedUser->forceUser(null);
            $this->impersonatedAccount->force(null, null);
            $this->loggedAccount->reset();
        }
    }

    private function commandAtExecutionTime(Command $command): Command
    {
        if ($command instanceof TimedCommand) {
            return $command->withNow(new \DateTimeImmutable('NOW'));
        }

        return $command;
    }
}
