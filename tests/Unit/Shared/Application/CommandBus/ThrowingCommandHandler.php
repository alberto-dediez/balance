<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandBus;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ThrowingCommandHandler implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(private readonly string $message = 'queue down')
    {
        $this->register();
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        throw new \RuntimeException($this->message);
    }

    public function isHandled(Command $command): bool
    {
        return true;
    }
}
