<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Notify;

use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;

class SpySystemNotifier implements SystemNotifier
{
    /** @var list<SystemNotification> */
    private array $notifications = [];

    public function notify(SystemNotification $notification): void
    {
        $this->notifications[] = $notification;
    }

    /** @return list<SystemNotification> */
    public function notifications(): array
    {
        return $this->notifications;
    }
}
