<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

enum NotificationChannel: string
{
    case Email = 'Email';
    case Sms = 'Sms';
    case Whatsapp = 'Whatsapp';
}
