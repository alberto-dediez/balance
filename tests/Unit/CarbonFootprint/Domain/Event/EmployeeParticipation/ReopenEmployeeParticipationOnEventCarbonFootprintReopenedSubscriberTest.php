<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EmployeeParticipation;

use App\CarbonFootprint\Application\EmployeeParticipation\Reopen\ReopenEmployeeParticipationService;
use App\CarbonFootprint\Domain\Event\EmployeeParticipation\ReopenEmployeeParticipationOnEventCarbonFootprintReopenedSubscriber;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationStatus;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintReopened;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventLocked;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class ReopenEmployeeParticipationOnEventCarbonFootprintReopenedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventCarbonFootprintReopened(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new EventCarbonFootprintReopened(EventCarbonFootprintId::generate())));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo(new EventLocked(EventId::generate())));
    }

    public function testHandleReopensTheEmployeeParticipationAndRemovesExtrapolatedActivities(): void
    {
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $participation = EmployeeParticipationMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $participation->close(5.0);

        $employeeParticipationRepository = new InMemoryEmployeeParticipationRepository();
        $employeeParticipationRepository->add($participation);

        $eventCarbonFootprint->addActivity(ActivityId::generate(), Phase::Assembly, Source::SuppliersAndStaffMobility, null, true);
        $manualActivityId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($manualActivityId, Phase::Assembly, Source::SuppliersAndStaffMobility);
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $sut = $this->createSUT($employeeParticipationRepository, $eventCarbonFootprintRepository);
        $sut->handle(new EventCarbonFootprintReopened($eventCarbonFootprint->id()));

        $reopened = $employeeParticipationRepository->find($participation->id());
        $this->assertNotNull($reopened);
        $this->assertSame(EmployeeParticipationStatus::Open, $reopened->status());

        $remaining = array_values($eventCarbonFootprint->activities()->activityIds());
        $this->assertCount(1, $remaining);
        $this->assertTrue($remaining[0]->equals($manualActivityId));
    }

    public function testHandleDoesNothingWhenThereIsNoParticipation(): void
    {
        $sut = $this->createSUT();

        $sut->handle(new EventCarbonFootprintReopened(EventCarbonFootprintId::generate()));

        $this->addToAssertionCount(1);
    }

    private function createSUT(
        ?InMemoryEmployeeParticipationRepository $employeeParticipationRepository = null,
        ?InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository = null,
    ): ReopenEmployeeParticipationOnEventCarbonFootprintReopenedSubscriber {
        return new ReopenEmployeeParticipationOnEventCarbonFootprintReopenedSubscriber(
            new ReopenEmployeeParticipationService(
                $employeeParticipationRepository ?? new InMemoryEmployeeParticipationRepository(),
                $eventCarbonFootprintRepository ?? EventCarbonFootprintRepositoryMother::inMemory(),
            ),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
