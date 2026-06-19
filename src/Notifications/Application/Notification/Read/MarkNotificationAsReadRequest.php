<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Read;

class MarkNotificationAsReadRequest
{
    public function __construct(public string $notificationId = '') {}
}
