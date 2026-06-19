<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Read;

use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Application\Notification\Read\MarkNotificationAsReadRequest;
use App\Notifications\Application\Notification\Read\MarkNotificationAsReadService;
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
}
