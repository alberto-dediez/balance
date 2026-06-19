<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\List;

use App\Notifications\Application\Notification\List\ListNotificationsRequest;
use App\Notifications\Application\Notification\List\ListNotificationsService;
use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationMother;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListNotificationsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAdminCanListAllNotifications(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $repository->add(NotificationMother::randomInternal());
        $repository->add(NotificationMother::randomInternal());

        $sut = new ListNotificationsService($repository, new NotificationAuthorizer());

        $response = $sut(new ListNotificationsRequest(), UserMother::adminUser());

        $this->assertCount(2, $response->notifications);
        $this->assertEquals(2, $response->totalResults);
    }

    public function testUserCanListOwnNotifications(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $repository->add(NotificationMother::internalFor($user->id()));
        $repository->add(NotificationMother::randomInternal());

        $sut = new ListNotificationsService($repository, new NotificationAuthorizer());

        $response = $sut(new ListNotificationsRequest(recipientId: $user->id()), $user);

        $this->assertCount(1, $response->notifications);
        $this->assertEquals(1, $response->totalResults);
    }

    public function testUserCannotListOtherUsersNotifications(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $sut = new ListNotificationsService($repository, new NotificationAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ListNotificationsRequest(), $user);
    }
}
