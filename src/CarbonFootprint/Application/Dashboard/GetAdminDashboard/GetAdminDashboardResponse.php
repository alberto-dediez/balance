<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard\GetAdminDashboard;

use App\CarbonFootprint\Application\Dashboard\DashboardCompensations;

class GetAdminDashboardResponse
{
    /**
     * @param array<int, float>    $emissionsByYear        co2eq (kg) indexed by calculation year
     * @param array<string, float> $emissionsByPhase       co2eq (kg) indexed by Phase value
     * @param array<string, float> $emissionsBySource      co2eq (kg) indexed by Source value
     * @param AccountEmissions[]   $topAccountsByEmissions
     * @param array<string, int>   $eventsByTypology       event count indexed by EventTypology value
     * @param FootprintOverview[]  $latestFootprints
     */
    public function __construct(
        public readonly int $totalAccounts,
        public readonly int $activeAccounts,
        public readonly int $totalUsers,
        public readonly int $totalEvents,
        public readonly int $eventsThisYear,
        public readonly int $totalFootprints,
        public readonly int $completedFootprints,
        public readonly int $inProgressFootprints,
        public readonly float $totalEmissionsCo2eq,
        public readonly float $averageEmissionsPerEventCo2eq,
        public readonly array $emissionsByYear,
        public readonly array $emissionsByPhase,
        public readonly array $emissionsBySource,
        public readonly array $topAccountsByEmissions,
        public readonly array $eventsByTypology,
        public readonly array $latestFootprints,
        public readonly DashboardCompensations $compensations,
    ) {}
}
