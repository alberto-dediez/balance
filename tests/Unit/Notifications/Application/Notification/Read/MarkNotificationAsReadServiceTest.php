<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Read;

use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Application\Notification\Read\MarkNotificationAsReadRequest;
use App\Notifications\Application\Notification\Read\MarkNotificationAsReadResponse;
use App\Notifications\Application\Notification\Read\MarkNotificationAsReadService;
use App\Notifications\Application\Notification\Remove\RemoveNotificationRequest;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Domain\Model\Notification\NotificationNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationMother;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MarkNotificationAsReadServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRecipientMarksOwnNotificationAsRead(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $notification = NotificationMother::internalFor($user->id());
        $repository->add($notification);

        $sut = new MarkNotificationAsReadService($repository, new NotificationAuthorizer());

        $sut(new MarkNotificationAsReadRequest((string) $notification->id()), $user);

        $recovered = $repository->find($notification->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($recovered->isRead());
    }

    public function testNonRecipientCannotMarkAsRead(): void
    {
        $repository = NotificationRepositoryMother::inMemory();

        $notification = NotificationMother::randomInternal();
        $repository->add($notification);

        $sut = new MarkNotificationAsReadService($repository, new NotificationAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new MarkNotificationAsReadRequest((string) $notification->id()), UserMother::fromUsernameAndRole('intruder', Role::User));
    }

    public function testNonRecipientAdminCannotMarkAsRead(): void
    {
        $repository = NotificationRepositoryMother::inMemory();

        $notification = NotificationMother::randomInternal();
        $repository->add($notification);

        $sut = new MarkNotificationAsReadService($repository, new NotificationAuthorizer());

        try {
            $sut(new MarkNotificationAsReadRequest((string) $notification->id()), UserMother::adminUser());
            $this->fail('Expected UnauthorizedActionException');
        } catch (UnauthorizedActionException) {
            $recovered = $repository->find($notification->id());

            $this->assertNotNull($recovered);
            $this->assertFalse($recovered->isRead());
        }
    }

    public function testReturnsResponseWithNotificationId(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $notification = NotificationMother::internalFor($user->id());
        $repository->add($notification);

        $sut = new MarkNotificationAsReadService($repository, new NotificationAuthorizer());

        $response = $sut(new MarkNotificationAsReadRequest((string) $notification->id()), $user);

        $this->assertInstanceOf(MarkNotificationAsReadResponse::class, $response);
        $this->assertEquals($notification->id(), $response->notificationId);
    }

    public function testIsHandledOnlyForMarkAsReadRequests(): void
    {
        $sut = new MarkNotificationAsReadService(NotificationRepositoryMother::inMemory(), new NotificationAuthorizer());

        $this->assertTrue($sut->isHandled(new MarkNotificationAsReadRequest()));
        $this->assertFalse($sut->isHandled(new RemoveNotificationRequest()));
    }

    public function testHandleDelegatesToInvoke(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $notification = NotificationMother::internalFor($user->id());
        $repository->add($notification);

        $sut = new MarkNotificationAsReadService($repository, new NotificationAuthorizer());

        $response = $sut->handle(new MarkNotificationAsReadRequest((string) $notification->id()), $user);

        $recovered = $repository->find($notification->id());

        $this->assertInstanceOf(MarkNotificationAsReadResponse::class, $response);
        $this->assertNotNull($recovered);
        $this->assertTrue($recovered->isRead());
    }

    public function testUnknownNotificationThrowsNotFound(): void
    {
        $sut = new MarkNotificationAsReadService(NotificationRepositoryMother::inMemory(), new NotificationAuthorizer());

        $this->expectException(NotificationNotFoundException::class);

        $sut(new MarkNotificationAsReadRequest((string) NotificationId::generate()), UserMother::fromUsernameAndRole('user', Role::User));
    }
}
