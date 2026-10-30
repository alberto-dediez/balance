<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Remove;

use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveNotificationResponse implements CommandResponse
{
    public function __construct(public NotificationId $notificationId) {}
}
