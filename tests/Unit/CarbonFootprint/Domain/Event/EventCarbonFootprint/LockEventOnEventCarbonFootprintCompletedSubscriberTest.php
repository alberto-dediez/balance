<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Event\EventCarbonFootprint\LockEventOnEventCarbonFootprintCompletedSubscriber;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintCompleted;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintProvidersAnswered;
use App\EventOperations\Application\Event\Lock\LockEventService;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LockEventOnEventCarbonFootprintCompletedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventCarbonFootprintCompleted(): void
    {
        $sut = $this->createSUT($this->createMock(LockEventService::class));

        $this->assertTrue($sut->isSubscribedTo(new EventCarbonFootprintCompleted(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
            totalEmissions: new Emissions(),
            emissionsPerAttendee: new Emissions(),
        )));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT($this->createMock(LockEventService::class));

        $this->assertFalse($sut->isSubscribedTo(new EventCarbonFootprintProvidersAnswered(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
        )));
    }

    public function testHandleLocksTheEvent(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId((string) EventId::generate());

        $service = $this->createMock(LockEventService::class);
        $service->expects($this->once())->method('__invoke');

        $sut = $this->createSUT($service, $eventCarbonFootprint);
        $sut->handle(new EventCarbonFootprintCompleted(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
            totalEmissions: new Emissions(),
            emissionsPerAttendee: new Emissions(),
        ));
    }

    public function testHandleDoesNothingWhenThereIsNoEventCarbonFootprint(): void
    {
        $service = $this->createMock(LockEventService::class);
        $service->expects($this->never())->method('__invoke');

        $sut = $this->createSUT($service);
        $sut->handle(new EventCarbonFootprintCompleted(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
            totalEmissions: new Emissions(),
            emissionsPerAttendee: new Emissions(),
        ));
    }

    /** @param LockEventService&MockObject $service */
    private function createSUT(
        MockObject $service,
        ?EventCarbonFootprint $eventCarbonFootprint = null,
    ): LockEventOnEventCarbonFootprintCompletedSubscriber {
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();

        if (null !== $eventCarbonFootprint) {
            $eventCarbonFootprintRepository->add($eventCarbonFootprint);
        }

        return new LockEventOnEventCarbonFootprintCompletedSubscriber(
            $service,
            $eventCarbonFootprintRepository,
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
