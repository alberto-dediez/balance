<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandBus;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class LocaleCapturingHandler implements CommandHandler
{
    use RegisterHandlerTrait;

    /** @var callable */
    private $onHandle;

    public function __construct(callable $onHandle)
    {
        $this->onHandle = $onHandle;
        $this->register();
    }

    public function isHandled(Command $command): bool
    {
        return true;
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        ($this->onHandle)();

        return null;
    }
}
