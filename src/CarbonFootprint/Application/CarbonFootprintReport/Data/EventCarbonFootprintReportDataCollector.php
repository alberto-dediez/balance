<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportActivityLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\EventOperations\Domain\Model\Event\EventRepository;

final class EventCarbonFootprintReportDataCollector implements CarbonFootprintReportDataCollector
{
    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventRepository $eventRepository,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private ActivityRepository $activityRepository,
        private EmissionsBreakdownBuilder $breakdownBuilder,
        private ActivityItemNameResolver $itemNameResolver,
        private EventCompensationCollector $compensationCollector,
    ) {}

    public function supports(CarbonFootprintReportSubjectType $subjectType): bool
    {
        return CarbonFootprintReportSubjectType::Event === $subjectType;
    }

    public function collect(CarbonFootprintReport $report): CarbonFootprintReportData
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find(new EventCarbonFootprintId($report->carbonFootprintId()));

        if (null === $eventCarbonFootprint) {
            throw new \RuntimeException('EventCarbonFootprint not found');
        }

        $eventId = $eventCarbonFootprint->eventId();
        $event = null !== $eventId ? $this->eventRepository->find($eventId) : null;

        if (null === $event) {
            throw new \RuntimeException('Event not found');
        }

        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId();
        $emissionFactorGroup = null !== $emissionFactorGroupId ? $this->emissionFactorGroupRepository->find($emissionFactorGroupId) : null;

        if (null === $emissionFactorGroup) {
            throw new \RuntimeException('EmissionFactorGroup not found');
        }

        $breakdown = $this->breakdownBuilder->build($eventCarbonFootprint, Phase::cases());

        $year = $eventCarbonFootprint->calculationYear();

        $activityLines = [];
        foreach ($eventCarbonFootprint->activities()->toArray() as $footprintActivity) {
            $activity = $this->activityRepository->findById($footprintActivity->activityId());

            if (null === $activity) {
                continue;
            }

            $activityLines[] = new EventCarbonFootprintReportActivityLine(
                itemName: $this->itemNameResolver->resolve($activity, $year, $emissionFactorGroupId),
                activityType: ActivityType::fromActivity($activity),
                phase: $footprintActivity->phase(),
                source: $footprintActivity->source(),
                quantity: $activity->quantity()->value(),
                unit: $activity->quantity()->unit(),
                factorCo2eq: $activity->emissionFactor()->co2eq(),
                factorUnit: $activity->emissionFactor()->unit(),
                factorYear: $activity->emissionFactor()->year(),
                factorSource: $activity->emissionFactor()->source(),
                emissionsCo2eq: $activity->emissions()->co2eq(),
            );
        }

        $exclusionLines = [];
        foreach ($eventCarbonFootprint->exclusions()->toArray() as $exclusion) {
            $exclusionLines[] = new EventCarbonFootprintReportExclusionLine(
                phase: $exclusion->phase(),
                source: $exclusion->source(),
                reason: $exclusion->reason(),
                comment: $exclusion->comment(),
            );
        }

        return new EventCarbonFootprintReportData(
            eventName: $event->name(),
            sector: $event->sector(),
            typology: $event->typology(),
            size: $event->size(),
            estimatedAttendees: $event->estimatedAttendees(),
            assembly: $event->assembly(),
            celebration: $event->celebration(),
            breakdown: $event->breakdown(),
            emissionFactorGroupName: $emissionFactorGroup->name(),
            calculationYear: $year,
            totalEmissions: $eventCarbonFootprint->totalEmissions(),
            emissionsPerAttendee: $eventCarbonFootprint->emissionsPerAttendee(),
            emissionsBySource: $breakdown->bySource,
            emissionsByPhase: $breakdown->byStage,
            emissionsByPhaseAndSource: $breakdown->byStageAndSource,
            activityLines: $activityLines,
            exclusionLines: $exclusionLines,
            compensation: $this->compensationCollector->__invoke($event->id(), $eventCarbonFootprint->totalEmissions()),
        );
    }
}
