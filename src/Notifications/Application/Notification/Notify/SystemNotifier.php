<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Notify;

/**
 * Port other bounded contexts depend on to route system events through the
 * Notifications bounded context, without coupling to its internals.
 */
interface SystemNotifier
{
    public function notify(SystemNotification $notification): void;
}
