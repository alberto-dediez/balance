<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard;

class GetOwnerDashboardResponse
{
    /**
     * @param array<int, float>    $emissionsByYear      co2eq (kg) indexed by calculation year
     * @param array<string, float> $emissionsByPhase     co2eq (kg) indexed by Phase value
     * @param array<string, float> $emissionsBySource    co2eq (kg) indexed by Source value
     * @param EventEmissions[]     $topEventsByEmissions
     * @param array<string, int>   $eventsByTypology     event count indexed by EventTypology value
     * @param FootprintOverview[]  $latestFootprints
     * @param array<string, float> $emissionsByProperty  co2eq (kg) indexed by Property name
     * @param array<string, float> $emissionsByCompany   co2eq (kg) indexed by Company name
     */
    public function __construct(
        public readonly int $totalEvents,
        public readonly int $eventsThisYear,
        public readonly int $totalEstimatedAttendees,
        public readonly int $totalFootprints,
        public readonly int $completedFootprints,
        public readonly int $inProgressFootprints,
        public readonly float $totalEmissionsCo2eq,
        public readonly float $averageEmissionsPerEventCo2eq,
        public readonly float $averageEmissionsPerAttendeeCo2eq,
        public readonly array $emissionsByYear,
        public readonly array $emissionsByPhase,
        public readonly array $emissionsBySource,
        public readonly array $topEventsByEmissions,
        public readonly array $eventsByTypology,
        public readonly array $latestFootprints,
        public readonly array $emissionsByProperty,
        public readonly array $emissionsByCompany,
    ) {}
}
