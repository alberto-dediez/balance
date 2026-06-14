<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

enum NotificationChannel: string
{
    case Email = 'Email';
    case Sms = 'Sms';
    case Whatsapp = 'Whatsapp';
    case Internal = 'Internal';
}
