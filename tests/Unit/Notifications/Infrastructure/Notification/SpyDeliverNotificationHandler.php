<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Infrastructure\Notification;

use App\Notifications\Application\Notification\Deliver\DeliverNotificationRequest;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class SpyDeliverNotificationHandler implements CommandHandler
{
    use RegisterHandlerTrait;

    /** @var list<DeliverNotificationRequest> */
    private array $requests = [];

    public function isHandled(Command $command): bool
    {
        return $command instanceof DeliverNotificationRequest;
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        if ($command instanceof DeliverNotificationRequest) {
            $this->requests[] = $command;
        }

        return null;
    }

    /** @return list<DeliverNotificationRequest> */
    public function requests(): array
    {
        return $this->requests;
    }
}
