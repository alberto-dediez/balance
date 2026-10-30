<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Remove;

use App\Shared\Application\CommandBus\Command;

class RemoveNotificationRequest implements Command
{
    public function __construct(public string $notificationId = '') {}
}
