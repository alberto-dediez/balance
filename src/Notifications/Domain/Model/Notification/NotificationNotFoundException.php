<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

final class NotificationNotFoundException extends \RuntimeException
{
    public function __construct(NotificationId $notificationId)
    {
        parent::__construct(sprintf('Notification "%s" not found', (string) $notificationId));
    }
}
