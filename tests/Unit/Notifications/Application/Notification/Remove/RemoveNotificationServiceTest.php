<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Remove;

use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Application\Notification\Read\MarkNotificationAsReadRequest;
use App\Notifications\Application\Notification\Remove\RemoveNotificationRequest;
use App\Notifications\Application\Notification\Remove\RemoveNotificationResponse;
use App\Notifications\Application\Notification\Remove\RemoveNotificationService;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Domain\Model\Notification\NotificationNotFoundException;
use App\Notifications\Domain\Model\Notification\NotificationRemoved;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationMother;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveNotificationServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testRecipientRemovesOwnNotification(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $notification = NotificationMother::internalFor($user->id());
        $repository->add($notification);

        $sut = new RemoveNotificationService($repository, new NotificationAuthorizer());

        $response = $sut(new RemoveNotificationRequest((string) $notification->id()), $user);

        $this->assertInstanceOf(RemoveNotificationResponse::class, $response);
        $this->assertEquals($notification->id(), $response->notificationId);
        $this->assertNull($repository->find($notification->id()));
        $this->assertInstanceOf(NotificationRemoved::class, $this->spy->lastEvent());
    }

    public function testAdminRemovesAnyNotification(): void
    {
        $repository = NotificationRepositoryMother::inMemory();

        $notification = NotificationMother::randomInternal();
        $repository->add($notification);

        $sut = new RemoveNotificationService($repository, new NotificationAuthorizer());

        $sut(new RemoveNotificationRequest((string) $notification->id()), UserMother::adminUser());

        $this->assertNull($repository->find($notification->id()));
    }

    public function testNonRecipientCannotRemove(): void
    {
        $repository = NotificationRepositoryMother::inMemory();

        $notification = NotificationMother::randomInternal();
        $repository->add($notification);

        $sut = new RemoveNotificationService($repository, new NotificationAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RemoveNotificationRequest((string) $notification->id()), UserMother::fromUsernameAndRole('intruder', Role::User));
    }

    public function testUnknownNotificationThrowsNotFound(): void
    {
        $sut = new RemoveNotificationService(NotificationRepositoryMother::inMemory(), new NotificationAuthorizer());

        $this->expectException(NotificationNotFoundException::class);

        $sut(new RemoveNotificationRequest((string) NotificationId::generate()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandledOnlyForRemoveRequests(): void
    {
        $sut = new RemoveNotificationService(NotificationRepositoryMother::inMemory(), new NotificationAuthorizer());

        $this->assertTrue($sut->isHandled(new RemoveNotificationRequest()));
        $this->assertFalse($sut->isHandled(new MarkNotificationAsReadRequest()));
    }
}
