<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintActivityRemoved;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintAlreadyCompletedException;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintAssemblyAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintBreakdownAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintCelebrationAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMaterialsAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintReopened;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class EventCarbonFootprintTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRetainActivitiesRemovesOnlyTheNonRetainedForThatPhaseAndSource(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $keptId = ActivityId::generate();
        $staleId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($keptId, Phase::Materials, Source::Materials);
        $eventCarbonFootprint->addActivity($staleId, Phase::Materials, Source::Materials);

        $eventCarbonFootprint->retainActivities(Phase::Materials, Source::Materials, [$keptId]);

        $activityIds = $eventCarbonFootprint->activities()->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($keptId));
    }

    public function testRetainActivitiesDoesNotTouchOtherPhasesOrSources(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $otherPhaseId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($otherPhaseId, Phase::Assembly, Source::MachineryOperation);

        $eventCarbonFootprint->retainActivities(Phase::Materials, Source::Materials, []);

        $activityIds = $eventCarbonFootprint->activities()->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($otherPhaseId));
    }

    public function testRetainActivitiesPublishesActivityRemoved(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $staleId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($staleId, Phase::Materials, Source::Materials);
        $eventCarbonFootprint->clearEvents();

        $eventCarbonFootprint->retainActivities(Phase::Materials, Source::Materials, []);

        $events = array_filter($eventCarbonFootprint->recordedEvents(), static fn($e) => $e instanceof EventCarbonFootprintActivityRemoved);
        $this->assertCount(1, $events);
    }

    public function testAnswerPhasePublishesTheCorrectEventAndTransitionsStatus(): void
    {
        $materials = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $materials->answerPhase(Phase::Materials);
        $this->assertSame(EventCarbonFootprintStatus::MaterialsAnswered, $materials->status());
        $this->assertEventPublished($materials, EventCarbonFootprintMaterialsAnswered::class);

        $assembly = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $assembly->answerPhase(Phase::Assembly);
        $this->assertSame(EventCarbonFootprintStatus::AssemblyAnswered, $assembly->status());
        $this->assertEventPublished($assembly, EventCarbonFootprintAssemblyAnswered::class);

        $celebration = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $celebration->answerPhase(Phase::Celebration);
        $this->assertSame(EventCarbonFootprintStatus::CelebrationAnswered, $celebration->status());
        $this->assertEventPublished($celebration, EventCarbonFootprintCelebrationAnswered::class);

        $breakdown = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $breakdown->answerPhase(Phase::Breakdown);
        $this->assertSame(EventCarbonFootprintStatus::BreakdownAnswered, $breakdown->status());
        $this->assertEventPublished($breakdown, EventCarbonFootprintBreakdownAnswered::class);
    }

    public function testReopenReturnsToStartedKeepingActivitiesAndResettingEmissions(): void
    {
        $eventCarbonFootprint = $this->completedEventCarbonFootprintWithOneActivity();

        $eventCarbonFootprint->reopen();

        $this->assertSame(EventCarbonFootprintStatus::Started, $eventCarbonFootprint->status());
        $this->assertSame('0', $eventCarbonFootprint->totalEmissions()->co2eq());
        $this->assertSame('0', $eventCarbonFootprint->emissionsPerAttendee()->co2eq());
        $this->assertCount(1, $eventCarbonFootprint->activities()->activityIds());
        $this->assertEventPublished($eventCarbonFootprint, EventCarbonFootprintReopened::class);
    }

    public function testUpdateThrowsWhenCompleted(): void
    {
        $eventCarbonFootprint = $this->completedEventCarbonFootprintWithOneActivity();

        $this->expectException(EventCarbonFootprintAlreadyCompletedException::class);
        $eventCarbonFootprint->update(new DateRange());
    }

    public function testAnswerPhaseThrowsWhenCompleted(): void
    {
        $eventCarbonFootprint = $this->completedEventCarbonFootprintWithOneActivity();

        $this->expectException(EventCarbonFootprintAlreadyCompletedException::class);
        $eventCarbonFootprint->answerPhase(Phase::Materials);
    }

    public function testAddActivityThrowsWhenCompleted(): void
    {
        $eventCarbonFootprint = $this->completedEventCarbonFootprintWithOneActivity();

        $this->expectException(EventCarbonFootprintAlreadyCompletedException::class);
        $eventCarbonFootprint->addActivity(ActivityId::generate(), Phase::Materials, Source::Materials);
    }

    public function testRemoveActivityThrowsWhenCompleted(): void
    {
        $eventCarbonFootprint = $this->completedEventCarbonFootprintWithOneActivity();
        $activityIds = $eventCarbonFootprint->activities()->activityIds();

        $this->expectException(EventCarbonFootprintAlreadyCompletedException::class);
        $eventCarbonFootprint->removeActivity($activityIds[0]);
    }

    public function testEditingIsAllowedAgainAfterReopen(): void
    {
        $eventCarbonFootprint = $this->completedEventCarbonFootprintWithOneActivity();

        $eventCarbonFootprint->reopen();
        $eventCarbonFootprint->answerPhase(Phase::Materials);

        $this->assertSame(EventCarbonFootprintStatus::MaterialsAnswered, $eventCarbonFootprint->status());
    }

    private function completedEventCarbonFootprintWithOneActivity(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $eventCarbonFootprint->addActivity(ActivityId::generate(), Phase::Materials, Source::Materials);
        $eventCarbonFootprint->complete(
            totalEmissions: new Emissions('30', '30', '0', '0'),
            emissionsPerAttendee: new Emissions('10', '10', '0', '0'),
        );

        return $eventCarbonFootprint;
    }

    private function assertEventPublished(mixed $aggregate, string $eventClass): void
    {
        $events = array_filter($aggregate->recordedEvents(), static fn($e) => $e instanceof $eventClass);
        $this->assertNotEmpty($events);
    }
}
