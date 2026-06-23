<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Notify;

use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\UserId;
use PHPUnit\Framework\TestCase;

class SystemNotificationTest extends TestCase
{
    public function testRequiresEitherBodyOrTemplate(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SystemNotification(
            recipientId: UserId::generate(),
            channel: NotificationChannel::Internal,
            subject: 'Hola',
        );
    }

    public function testIsValidWithBody(): void
    {
        $notification = new SystemNotification(
            recipientId: UserId::generate(),
            channel: NotificationChannel::Internal,
            subject: 'Hola',
            body: 'Mensaje',
        );

        $this->assertFalse($notification->hasTemplate());
    }

    public function testIsValidWithTemplate(): void
    {
        $notification = new SystemNotification(
            recipientId: UserId::generate(),
            channel: NotificationChannel::Email,
            subject: 'Hola',
            template: '@some/template.html.twig',
            params: ['foo' => 'bar'],
        );

        $this->assertTrue($notification->hasTemplate());
    }
}
