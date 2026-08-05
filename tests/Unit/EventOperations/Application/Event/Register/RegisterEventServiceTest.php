<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Event\Register;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Application\Event\EventVenueLocationSetter;
use App\EventOperations\Application\Event\Register\RegisterEventRequest;
use App\EventOperations\Application\Event\Register\RegisterEventService;
use App\EventOperations\Domain\Model\Event\EventRegistered;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class RegisterEventServiceTest extends TestCase
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

        $sut = $this->createSUT($repository);

        $accountId = new AccountId('account-id-test');

        $request = new RegisterEventRequest(
            name: 'Event Test',
            accountId: $accountId,
        );

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $this->assertNotEmpty((string) $result->eventId);
        $this->assertEquals(36, strlen((string) $result->eventId));
        $this->assertEquals('Event Test', $result->name);

        $eventPersisted = $repository->find($result->eventId);

        $this->assertNotNull($eventPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EventRegistered);
        $this->assertEquals('Event Test', $lastEvent->name());
        $this->assertTrue($eventPersisted->id()->equals($lastEvent->eventId()));
    }

    public function testIsHandle(): void
    {
        $repository = EventRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterEventRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EventRepository $repository): RegisterEventService
    {
        return new RegisterEventService(
            eventRepository: $repository,
            authorizer: new EventAuthorizer(),
            venueLocationSetter: new EventVenueLocationSetter(new InMemoryPlaceFinder()),
        );
    }
}
