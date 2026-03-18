<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandBus;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class SluttyCommandHandler implements CommandHandler
{
    use RegisterHandlerTrait;

    /** @param array<int, mixed> $commands */
    public function __construct(private array $commands = [])
    {
        $this->register();
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        $this->commands[] = [
            'command' => $command,
            'user' => $user,
        ];

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return true;
    }

    /** @return array<int, mixed> */
    public function commands(): array
    {
        return $this->commands;
    }

    public function lastCommand(): ?Command
    {
        return $this->commands[count($this->commands) - 1] ?? null
        ;
    }
}
