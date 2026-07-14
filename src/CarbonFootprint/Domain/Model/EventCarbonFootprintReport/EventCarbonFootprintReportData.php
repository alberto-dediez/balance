<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventSector;
use App\EventOperations\Domain\Model\Event\EventSize;
use App\EventOperations\Domain\Model\Event\EventTypology;

final class EventCarbonFootprintReportData
{
    /**
     * @param array<string, Emissions>                     $emissionsBySource
     * @param array<string, Emissions>                     $emissionsByPhase
     * @param array<string, array<string, Emissions>>      $emissionsByPhaseAndSource
     * @param list<EventCarbonFootprintReportActivityLine> $activityLines
     */
    public function __construct(
        public readonly string $eventName,
        public readonly ?EventSector $sector,
        public readonly ?EventTypology $typology,
        public readonly ?EventSize $size,
        public readonly ?int $estimatedAttendees,
        public readonly DateRange $assembly,
        public readonly DateRange $celebration,
        public readonly DateRange $breakdown,
        public readonly string $emissionFactorGroupName,
        public readonly int $calculationYear,
        public readonly Emissions $totalEmissions,
        public readonly Emissions $emissionsPerAttendee,
        public readonly array $emissionsBySource,
        public readonly array $emissionsByPhase,
        public readonly array $emissionsByPhaseAndSource,
        public readonly array $activityLines,
    ) {}
}
