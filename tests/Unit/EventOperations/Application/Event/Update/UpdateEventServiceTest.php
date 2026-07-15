<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Event\Update;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Application\Event\Update\UpdateEventRequest;
use App\EventOperations\Application\Event\Update\UpdateEventService;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventLockedException;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Event\EventUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateEventServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = EventRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id-test', (string) EventId::generate());

        $repository->add($event);

        $sut = $this->createSUT($repository);

        $request = new UpdateEventRequest(
            eventId: $event->id(),
            name: 'Updated Name',
        );

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $this->assertEquals('Updated Name', $result->name);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EventUpdated);
        $this->assertEquals('Updated Name', $lastEvent->name());
    }

    public function testCannotUpdateWhenEventIsLocked(): void
    {
        $repository = EventRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id-test', (string) EventId::generate());
        $event->lock();

        $repository->add($event);

        $sut = $this->createSUT($repository);

        $request = new UpdateEventRequest(
            eventId: $event->id(),
            name: 'Updated Name',
        );

        $this->expectException(EventLockedException::class);

        $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));
    }

    private function createSUT(EventRepository $repository): UpdateEventService
    {
        return new UpdateEventService(
            eventRepository: $repository,
            authorizer: new EventAuthorizer(),
        );
    }
}
