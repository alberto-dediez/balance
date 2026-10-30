<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Read;

use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Shared\Application\CommandBus\CommandResponse;

class MarkNotificationAsReadResponse implements CommandResponse
{
    public function __construct(public NotificationId $notificationId) {}
}
