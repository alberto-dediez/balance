<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Deliver;

use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Shared\Application\CommandBus\Command;

class DeliverNotificationRequest implements Command
{
    public function __construct(
        public string $recipientId = '',
        public NotificationChannel $channel = NotificationChannel::Internal,
        public string $subject = '',
        public string $body = '',
        public ?string $accountId = null,
        public ?string $sentBy = null,
    ) {}
}
