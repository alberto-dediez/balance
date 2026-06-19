<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

enum AudienceType: string
{
    case User = 'User';
    case Account = 'Account';
    case AccountRole = 'AccountRole';
    case AllPlatform = 'AllPlatform';
}
