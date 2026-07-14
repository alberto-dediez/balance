<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\Update\UpdateEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Event\EventCarbonFootprint\UpdateCalculationPeriodOnEventUpdatedSubscriber;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRegistered;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Event\EventUpdated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateCalculationPeriodOnEventUpdatedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventUpdated(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo($this->eventUpdated((string) EventId::generate())));
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

    public function testHandleUpdatesCalculationPeriodWhenDatesChanged(): void
    {
        $eventId = (string) EventId::generate();
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId($eventId);

        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $eventRepository = EventRepositoryMother::inMemory();
        $eventRepository->add(EventMother::fromNameAndId('Event', $eventId));

        $service = $this->createMock(UpdateEventCarbonFootprintService::class);
        $service->expects($this->once())->method('__invoke');

        $sut = $this->createSUT($eventRepository, $eventCarbonFootprintRepository, $service);
        $sut->handle($this->eventUpdated($eventId));
    }

    public function testHandleDoesNothingWhenDatesUnchanged(): void
    {
        $eventId = (string) EventId::generate();
        $event = EventMother::fromNameAndId('Event', $eventId);

        $eventCarbonFootprint = EventCarbonFootprintMother::from(
            eventId: $eventId,
            id: (string) EventCarbonFootprintId::generate(),
        );
        $eventCarbonFootprint->update(
            calculationPeriod: new DateRange(
                startDate: $event->assembly()->startDate(),
                endDate: $event->breakdown()->endDate(),
            ),
        );

        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $eventRepository = EventRepositoryMother::inMemory();
        $eventRepository->add($event);

        $service = $this->createMock(UpdateEventCarbonFootprintService::class);
        $service->expects($this->never())->method('__invoke');

        $sut = $this->createSUT($eventRepository, $eventCarbonFootprintRepository, $service);
        $sut->handle($this->eventUpdated($eventId));
    }

    public function testHandleDoesNothingWhenNoEventCarbonFootprintExists(): void
    {
        $eventId = (string) EventId::generate();

        $eventRepository = EventRepositoryMother::inMemory();
        $eventRepository->add(EventMother::fromNameAndId('Event', $eventId));

        $service = $this->createMock(UpdateEventCarbonFootprintService::class);
        $service->expects($this->never())->method('__invoke');

        $sut = $this->createSUT($eventRepository, EventCarbonFootprintRepositoryMother::inMemory(), $service);
        $sut->handle($this->eventUpdated($eventId));
    }

    public function testHandleDoesNothingWhenEventNotFound(): void
    {
        $eventId = (string) EventId::generate();
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId($eventId);

        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $service = $this->createMock(UpdateEventCarbonFootprintService::class);
        $service->expects($this->never())->method('__invoke');

        $sut = $this->createSUT(EventRepositoryMother::inMemory(), $eventCarbonFootprintRepository, $service);
        $sut->handle($this->eventUpdated($eventId));
    }

    private function eventUpdated(string $eventId): EventUpdated
    {
        return new EventUpdated(
            eventId: new EventId($eventId),
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
        );
    }

    private function createSUT(
        ?EventRepository $eventRepository = null,
        ?EventCarbonFootprintRepository $eventCarbonFootprintRepository = null,
        ?MockObject $service = null,
    ): UpdateCalculationPeriodOnEventUpdatedSubscriber {
        /** @var UpdateEventCarbonFootprintService&MockObject $updateEventCarbonFootprint */
        $updateEventCarbonFootprint = $service ?? $this->createMock(UpdateEventCarbonFootprintService::class);

        return new UpdateCalculationPeriodOnEventUpdatedSubscriber(
            $eventRepository ?? EventRepositoryMother::inMemory(),
            $eventCarbonFootprintRepository ?? EventCarbonFootprintRepositoryMother::inMemory(),
            $updateEventCarbonFootprint,
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
