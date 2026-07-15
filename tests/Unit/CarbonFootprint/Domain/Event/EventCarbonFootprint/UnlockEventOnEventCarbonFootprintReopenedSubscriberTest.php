<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Event\EventCarbonFootprint\UnlockEventOnEventCarbonFootprintReopenedSubscriber;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintProvidersAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintReopened;
use App\EventOperations\Application\Event\Unlock\UnlockEventService;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UnlockEventOnEventCarbonFootprintReopenedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventCarbonFootprintReopened(): void
    {
        $sut = $this->createSUT($this->createMock(UnlockEventService::class));

        $this->assertTrue($sut->isSubscribedTo(new EventCarbonFootprintReopened(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
        )));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT($this->createMock(UnlockEventService::class));

        $this->assertFalse($sut->isSubscribedTo(new EventCarbonFootprintProvidersAnswered(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
        )));
    }

    public function testHandleUnlocksTheEvent(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId((string) EventId::generate());

        $service = $this->createMock(UnlockEventService::class);
        $service->expects($this->once())->method('__invoke');

        $sut = $this->createSUT($service, $eventCarbonFootprint);
        $sut->handle(new EventCarbonFootprintReopened(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
        ));
    }

    public function testHandleDoesNothingWhenThereIsNoEventCarbonFootprint(): void
    {
        $service = $this->createMock(UnlockEventService::class);
        $service->expects($this->never())->method('__invoke');

        $sut = $this->createSUT($service);
        $sut->handle(new EventCarbonFootprintReopened(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
        ));
    }

    /** @param UnlockEventService&MockObject $service */
    private function createSUT(
        MockObject $service,
        ?EventCarbonFootprint $eventCarbonFootprint = null,
    ): UnlockEventOnEventCarbonFootprintReopenedSubscriber {
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();

        if (null !== $eventCarbonFootprint) {
            $eventCarbonFootprintRepository->add($eventCarbonFootprint);
        }

        return new UnlockEventOnEventCarbonFootprintReopenedSubscriber(
            $service,
            $eventCarbonFootprintRepository,
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
