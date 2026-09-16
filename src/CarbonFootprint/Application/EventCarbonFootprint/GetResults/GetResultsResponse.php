<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetResults;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\Emissions;

final class GetResultsResponse
{
    /**
     * @param array<string, Emissions>                $emissionsBySource
     * @param array<string, Emissions>                $emissionsByPhase
     * @param array<string, array<string, Emissions>> $emissionsByPhaseAndSource
     * @param list<SourceExclusion>                   $exclusions
     */
    public function __construct(
        public readonly Emissions $totalEmissions,
        public readonly Emissions $emissionsPerAttendee,
        public readonly array $emissionsBySource,
        public readonly array $emissionsByPhase,
        public readonly array $emissionsByPhaseAndSource,
        public readonly array $exclusions = [],
    ) {}
}
