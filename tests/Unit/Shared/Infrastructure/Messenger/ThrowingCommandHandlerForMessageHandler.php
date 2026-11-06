<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ThrowingCommandHandlerForMessageHandler implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct()
    {
        $this->register();
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        throw new \RuntimeException('Something went wrong while handling the command');
    }

    public function isHandled(Command $command): bool
    {
        return true;
    }
}
