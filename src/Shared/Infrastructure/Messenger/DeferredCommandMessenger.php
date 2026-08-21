<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\DeferredCommandHandler;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

class DeferredCommandMessenger implements DeferredCommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
        private ImpersonatedAccount $impersonatedAccount,
    ) {}

    public function isHandled(Command $command): bool
    {
        return $command instanceof DeferredCommand;
    }

    /**
     * @param DeferredCommand $command
     */
    public function handle(Command $command, User $user): ?CommandResponse
    {
        $impersonatedAccountId = $this->impersonatedAccount->accountId();

        $message = match ($command->priority()) {
            Priority::HIGH => new DeferredHighMessage($command->command(), $user->id(), $impersonatedAccountId),
            Priority::LOW  => new DeferredLowMessage($command->command(), $user->id(), $impersonatedAccountId),
            default        => new DeferredMediumMessage($command->command(), $user->id(), $impersonatedAccountId),
        };

        $this->messageBus->dispatch($message, $this->stamps($command));

        return new EnqueueCommandResponse();
    }

    /**
     * @return StampInterface[]
     */
    private function stamps(DeferredCommand $command): array
    {
        $executeAt = $command->executeAt();

        if (!$executeAt instanceof \DateTimeImmutable) {
            return [];
        }

        $delayInMilliseconds = ($executeAt->getTimestamp() - time()) * 1000;

        if ($delayInMilliseconds <= 0) {
            return [];
        }

        return [new DelayStamp($delayInMilliseconds)];
    }
}
