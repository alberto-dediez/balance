<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Remove;

class RemoveNotificationRequest
{
    public function __construct(public string $notificationId = '') {}
}
