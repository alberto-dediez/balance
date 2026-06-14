<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Domain\Model\Notification;

use App\Notifications\Domain\Model\Notification\NotificationCreated;
use App\Notifications\Domain\Model\Notification\NotificationDeliveryFailed;
use App\Notifications\Domain\Model\Notification\NotificationRead;
use App\Notifications\Domain\Model\Notification\NotificationSent;
use App\Notifications\Domain\Model\Notification\NotificationStatus;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testCreateRecordsNotificationCreated(): void
    {
        $notification = NotificationMother::internalFor(UserId::generate());

        $this->assertEquals(NotificationStatus::Pending, $notification->status());
        $this->assertFalse($notification->isRead());
        $this->assertInstanceOf(NotificationCreated::class, $this->spy->lastEvent());
    }

    public function testMarkAsSent(): void
    {
        $notification = NotificationMother::internalFor(UserId::generate());

        $notification->markAsSent();

        $this->assertEquals(NotificationStatus::Sent, $notification->status());
        $this->assertNotNull($notification->sentAt());
        $this->assertInstanceOf(NotificationSent::class, $this->spy->lastEvent());
    }

    public function testMarkAsFailed(): void
    {
        $notification = NotificationMother::emailFor(UserId::generate());

        $notification->markAsFailed('boom');

        $this->assertEquals(NotificationStatus::Failed, $notification->status());
        $this->assertEquals('boom', $notification->failureReason());
        $this->assertInstanceOf(NotificationDeliveryFailed::class, $this->spy->lastEvent());
    }

    public function testMarkAsReadStoresWhenItHappened(): void
    {
        $notification = NotificationMother::internalFor(UserId::generate());

        $notification->markAsRead();

        $this->assertTrue($notification->isRead());
        $this->assertNotNull($notification->readAt());
        $this->assertInstanceOf(NotificationRead::class, $this->spy->lastEvent());
    }

    public function testMarkAsReadIsIdempotent(): void
    {
        $notification = NotificationMother::internalFor(UserId::generate());

        $notification->markAsRead();
        $firstReadAt = $notification->readAt();

        $notification->markAsRead();

        $this->assertEquals($firstReadAt, $notification->readAt());
    }
}
