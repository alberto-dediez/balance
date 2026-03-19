<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\DeferredCommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use Symfony\Component\Messenger\MessageBusInterface;

class DeferredCommandMessenger implements DeferredCommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(private MessageBusInterface $messageBus) {}

    public function isHandled(Command $command): bool
    {
        return $command instanceof DeferredCommand;
    }

    /**
     * @param DeferredCommand $command
     */
    public function handle(Command $command, User $user): ?CommandResponse
    {
        $this->messageBus->dispatch(new DeferredMessage($command->command(), $user->id()));

        return new EnqueueCommandResponse();
    }
}
