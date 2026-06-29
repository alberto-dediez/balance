<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Event\Remove;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Application\Event\Remove\RemoveEventRequest;
use App\EventOperations\Application\Event\Remove\RemoveEventService;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRemoved;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveEventServiceTest extends TestCase
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

        $sut(new RemoveEventRequest($event->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EventRemoved);
        $this->assertTrue($event->id()->equals($lastEvent->eventId()));
    }

    private function createSUT(EventRepository $repository): RemoveEventService
    {
        return new RemoveEventService(
            repository: $repository,
            authorizer: new EventAuthorizer(),
        );
    }
}
