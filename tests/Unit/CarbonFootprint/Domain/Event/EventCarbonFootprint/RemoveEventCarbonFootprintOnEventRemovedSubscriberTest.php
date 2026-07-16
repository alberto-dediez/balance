<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\Remove\RemoveEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Event\EventCarbonFootprint\RemoveEventCarbonFootprintOnEventRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRegistered;
use App\EventOperations\Domain\Model\Event\EventRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveEventCarbonFootprintOnEventRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventRemoved(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo($this->eventRemoved((string) EventId::generate())));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo(new EventRegistered(
            eventId: EventId::generate(),
            name: 'Event',
            location: null,
            propertyId: null,
            companyId: null,
            size: null,
            estimatedAttendees: null,
            sector: null,
            typology: null,
            assembly: new DateRange(),
            celebration: new DateRange(),
            breakdown: new DateRange(),
            accountId: null,
        )));
    }

    public function testHandleCallsRemoveEventCarbonFootprintServiceWhenFound(): void
    {
        $eventId = (string) EventId::generate();
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId($eventId);

        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $service = $this->createMock(RemoveEventCarbonFootprintService::class);
        $service->expects($this->once())->method('__invoke');

        $sut = $this->createSUT($eventCarbonFootprintRepository, $service);
        $sut->handle($this->eventRemoved($eventId));
    }

    public function testHandleDoesNothingWhenNoEventCarbonFootprintExists(): void
    {
        $service = $this->createMock(RemoveEventCarbonFootprintService::class);
        $service->expects($this->never())->method('__invoke');

        $sut = $this->createSUT(EventCarbonFootprintRepositoryMother::inMemory(), $service);
        $sut->handle($this->eventRemoved((string) EventId::generate()));
    }

    private function eventRemoved(string $eventId): EventRemoved
    {
        return new EventRemoved(eventId: new EventId($eventId));
    }

    private function createSUT(
        ?EventCarbonFootprintRepository $eventCarbonFootprintRepository = null,
        ?MockObject $service = null,
    ): RemoveEventCarbonFootprintOnEventRemovedSubscriber {
        /** @var RemoveEventCarbonFootprintService&MockObject $removeEventCarbonFootprint */
        $removeEventCarbonFootprint = $service ?? $this->createMock(RemoveEventCarbonFootprintService::class);

        return new RemoveEventCarbonFootprintOnEventRemovedSubscriber(
            $eventCarbonFootprintRepository ?? EventCarbonFootprintRepositoryMother::inMemory(),
            $removeEventCarbonFootprint,
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
