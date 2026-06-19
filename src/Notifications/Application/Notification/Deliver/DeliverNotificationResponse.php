<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Deliver;

use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Shared\Application\CommandBus\CommandResponse;

class DeliverNotificationResponse implements CommandResponse
{
    public function __construct(public NotificationId $notificationId) {}
}
