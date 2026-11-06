<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Unread;

use App\Shared\Application\CommandBus\Command;

class MarkNotificationAsUnreadRequest implements Command
{
    public function __construct(public string $notificationId = '') {}
}
