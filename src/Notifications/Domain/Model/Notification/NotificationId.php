<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class NotificationId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
