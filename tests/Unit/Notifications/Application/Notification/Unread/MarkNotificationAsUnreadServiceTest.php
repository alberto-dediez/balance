<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Unread;

use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Application\Notification\Read\MarkNotificationAsReadRequest;
use App\Notifications\Application\Notification\Unread\MarkNotificationAsUnreadRequest;
use App\Notifications\Application\Notification\Unread\MarkNotificationAsUnreadResponse;
use App\Notifications\Application\Notification\Unread\MarkNotificationAsUnreadService;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Domain\Model\Notification\NotificationNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationMother;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MarkNotificationAsUnreadServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRecipientMarksOwnNotificationAsUnread(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $notification = NotificationMother::internalFor($user->id());
        $notification->markAsRead();
        $repository->add($notification);

        $sut = new MarkNotificationAsUnreadService($repository, new NotificationAuthorizer());

        $sut(new MarkNotificationAsUnreadRequest((string) $notification->id()), $user);

        $recovered = $repository->find($notification->id());

        $this->assertNotNull($recovered);
        $this->assertFalse($recovered->isRead());
    }

    public function testNonRecipientCannotMarkAsUnread(): void
    {
        $repository = NotificationRepositoryMother::inMemory();

        $notification = NotificationMother::randomInternal();
        $notification->markAsRead();
        $repository->add($notification);

        $sut = new MarkNotificationAsUnreadService($repository, new NotificationAuthorizer());

        try {
            $sut(new MarkNotificationAsUnreadRequest((string) $notification->id()), UserMother::fromUsernameAndRole('intruder', Role::User));
            $this->fail('Expected UnauthorizedActionException');
        } catch (UnauthorizedActionException) {
            $recovered = $repository->find($notification->id());

            $this->assertNotNull($recovered);
            $this->assertTrue($recovered->isRead());
        }
    }

    public function testNonRecipientAdminCannotMarkAsUnread(): void
    {
        $repository = NotificationRepositoryMother::inMemory();

        $notification = NotificationMother::randomInternal();
        $notification->markAsRead();
        $repository->add($notification);

        $sut = new MarkNotificationAsUnreadService($repository, new NotificationAuthorizer());

        try {
            $sut(new MarkNotificationAsUnreadRequest((string) $notification->id()), UserMother::adminUser());
            $this->fail('Expected UnauthorizedActionException');
        } catch (UnauthorizedActionException) {
            $recovered = $repository->find($notification->id());

            $this->assertNotNull($recovered);
            $this->assertTrue($recovered->isRead());
        }
    }

    public function testReturnsResponseWithNotificationId(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $notification = NotificationMother::internalFor($user->id());
        $notification->markAsRead();
        $repository->add($notification);

        $sut = new MarkNotificationAsUnreadService($repository, new NotificationAuthorizer());

        $response = $sut(new MarkNotificationAsUnreadRequest((string) $notification->id()), $user);

        $this->assertInstanceOf(MarkNotificationAsUnreadResponse::class, $response);
        $this->assertEquals($notification->id(), $response->notificationId);
    }

    public function testIsHandledOnlyForMarkAsUnreadRequests(): void
    {
        $sut = new MarkNotificationAsUnreadService(NotificationRepositoryMother::inMemory(), new NotificationAuthorizer());

        $this->assertTrue($sut->isHandled(new MarkNotificationAsUnreadRequest()));
        $this->assertFalse($sut->isHandled(new MarkNotificationAsReadRequest()));
    }

    public function testHandleDelegatesToInvoke(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $notification = NotificationMother::internalFor($user->id());
        $notification->markAsRead();
        $repository->add($notification);

        $sut = new MarkNotificationAsUnreadService($repository, new NotificationAuthorizer());

        $response = $sut->handle(new MarkNotificationAsUnreadRequest((string) $notification->id()), $user);

        $recovered = $repository->find($notification->id());

        $this->assertInstanceOf(MarkNotificationAsUnreadResponse::class, $response);
        $this->assertNotNull($recovered);
        $this->assertFalse($recovered->isRead());
    }

    public function testUnknownNotificationThrowsNotFound(): void
    {
        $sut = new MarkNotificationAsUnreadService(NotificationRepositoryMother::inMemory(), new NotificationAuthorizer());

        $this->expectException(NotificationNotFoundException::class);

        $sut(new MarkNotificationAsUnreadRequest((string) NotificationId::generate()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testMarkingAnUnreadNotificationIsAccepted(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $notification = NotificationMother::internalFor($user->id());
        $repository->add($notification);

        $sut = new MarkNotificationAsUnreadService($repository, new NotificationAuthorizer());

        $sut(new MarkNotificationAsUnreadRequest((string) $notification->id()), $user);

        $recovered = $repository->find($notification->id());

        $this->assertNotNull($recovered);
        $this->assertFalse($recovered->isRead());
    }
}
