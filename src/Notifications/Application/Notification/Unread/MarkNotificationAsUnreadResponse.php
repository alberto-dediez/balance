<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Unread;

use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Shared\Application\CommandBus\CommandResponse;

class MarkNotificationAsUnreadResponse implements CommandResponse
{
    public function __construct(public NotificationId $notificationId) {}
}
