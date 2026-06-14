<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Domain\Model\Notification;

use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

class NotificationMother
{
    public static function internalFor(UserId $recipientId): Notification
    {
        return self::from($recipientId, NotificationChannel::Internal);
    }

    public static function emailFor(UserId $recipientId): Notification
    {
        return self::from($recipientId, NotificationChannel::Email);
    }

    public static function randomInternal(): Notification
    {
        return self::internalFor(UserId::generate());
    }

    public static function from(
        UserId $recipientId,
        NotificationChannel $channel = NotificationChannel::Internal,
        string $subject = 'Asunto',
        string $body = 'Cuerpo',
        ?AccountId $accountId = null,
        ?UserId $sentBy = null,
    ): Notification {
        $notification = new Notification(NotificationId::generate());

        $notification->create(
            recipientId: $recipientId,
            channel: $channel,
            subject: $subject,
            body: $body,
            accountId: $accountId,
            sentBy: $sentBy,
        );

        return $notification;
    }
}
