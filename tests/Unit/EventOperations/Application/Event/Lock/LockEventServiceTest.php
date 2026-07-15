<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Event\Lock;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Application\Event\Lock\LockEventRequest;
use App\EventOperations\Application\Event\Lock\LockEventService;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventLocked;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class LockEventServiceTest extends TestCase
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

        $sut(new LockEventRequest($event->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $this->assertTrue($event->isLocked());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EventLocked);
    }

    public function testLockingAnAlreadyLockedEventDoesNothing(): void
    {
        $repository = EventRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id-test', (string) EventId::generate());
        $event->lock();

        $repository->add($event);

        $eventsBefore = $this->spySubscriber->events();

        $sut = $this->createSUT($repository);

        $sut(new LockEventRequest($event->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $this->assertTrue($event->isLocked());
        $this->assertCount(count($eventsBefore), $this->spySubscriber->events());
    }

    private function createSUT(EventRepository $repository): LockEventService
    {
        return new LockEventService(
            eventRepository: $repository,
            authorizer: new EventAuthorizer(),
        );
    }
}
