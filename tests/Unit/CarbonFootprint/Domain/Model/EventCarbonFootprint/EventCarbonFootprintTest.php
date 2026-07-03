<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintActivityRemoved;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintAssemblyAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintBreakdownAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintCelebrationAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMaterialsAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
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

    private function assertEventPublished(mixed $aggregate, string $eventClass): void
    {
        $events = array_filter($aggregate->recordedEvents(), static fn($e) => $e instanceof $eventClass);
        $this->assertNotEmpty($events);
    }
}
