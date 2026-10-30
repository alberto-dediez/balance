<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Read;

use App\Shared\Application\CommandBus\Command;

class MarkNotificationAsReadRequest implements Command
{
    public function __construct(public string $notificationId = '') {}
}
