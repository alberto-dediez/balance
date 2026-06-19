<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

enum NotificationStatus: string
{
    case Pending = 'Pending';
    case Sent = 'Sent';
    case Failed = 'Failed';
}
