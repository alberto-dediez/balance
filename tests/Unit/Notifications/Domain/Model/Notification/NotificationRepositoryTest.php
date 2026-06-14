<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Domain\Model\Notification;

use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Domain\Model\Notification\NotificationFilter;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Domain\Model\User\UserId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class NotificationRepositoryTest extends KernelTestCase
{
    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();

        $notification = NotificationMother::randomInternal();

        $this->assertNull($repository->find($notification->id()));

        $repository->add($notification);

        $recovered = $repository->find($notification->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($notification->id(), $recovered->id());
    }

    public function testRemove(): void
    {
        $repository = $this->createSUT();

        $notification = NotificationMother::randomInternal();
        $repository->add($notification);

        $repository->remove($notification);

        $this->assertNull($repository->find($notification->id()));
    }

    public function testFindByFilterByRecipient(): void
    {
        $repository = $this->createSUT();

        $recipient = UserId::generate();

        $repository->add(NotificationMother::internalFor($recipient));
        $repository->add(NotificationMother::internalFor($recipient));
        $repository->add(NotificationMother::randomInternal());

        $result = $repository->findByFilter(new NotificationFilter(recipientId: $recipient, limit: 0));

        $this->assertCount(2, $result);
    }

    public function testFindByFilterByChannel(): void
    {
        $repository = $this->createSUT();

        $recipient = UserId::generate();
        $repository->add(NotificationMother::internalFor($recipient));
        $repository->add(NotificationMother::emailFor($recipient));

        $result = $repository->findByFilter(new NotificationFilter(channel: NotificationChannel::Email, limit: 0));

        $this->assertCount(1, $result);
        $this->assertEquals(NotificationChannel::Email, $result[0]->channel());
    }

    public function testCountUnreadByRecipientOnlyCountsInternalUnread(): void
    {
        $repository = $this->createSUT();

        $recipient = UserId::generate();

        $repository->add(NotificationMother::internalFor($recipient));
        $repository->add(NotificationMother::internalFor($recipient));
        $repository->add(NotificationMother::emailFor($recipient));

        $read = NotificationMother::internalFor($recipient);
        $read->markAsRead();
        $repository->add($read);

        $this->assertEquals(2, $repository->countUnreadByRecipient($recipient));
        $this->assertCount(2, $repository->findUnreadByRecipient($recipient, 0));
    }

    abstract protected function createSUT(): NotificationRepository;
}
