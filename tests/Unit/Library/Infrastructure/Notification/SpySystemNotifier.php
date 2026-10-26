<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Notification;

use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;

class SpySystemNotifier implements SystemNotifier
{
    /** @var array<int, SystemNotification> */
    public array $notifications = [];

    public function notify(SystemNotification $notification): void
    {
        $this->notifications[] = $notification;
    }
}
