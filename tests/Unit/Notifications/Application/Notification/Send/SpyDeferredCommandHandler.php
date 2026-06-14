<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Send;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class SpyDeferredCommandHandler implements CommandHandler
{
    use RegisterHandlerTrait;

    /** @var list<DeferredCommand> */
    private array $commands = [];

    public function isHandled(Command $command): bool
    {
        return $command instanceof DeferredCommand;
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        if ($command instanceof DeferredCommand) {
            $this->commands[] = $command;
        }

        return null;
    }

    /** @return list<DeferredCommand> */
    public function commands(): array
    {
        return $this->commands;
    }
}
