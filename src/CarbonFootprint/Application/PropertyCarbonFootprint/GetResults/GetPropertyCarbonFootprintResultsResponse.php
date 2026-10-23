<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\GetResults;

use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\Emissions;

final class GetPropertyCarbonFootprintResultsResponse
{
    /**
     * @param array<string, Emissions>                $emissionsBySource
     * @param array<string, Emissions>                $emissionsByScope
     * @param array<string, array<string, Emissions>> $emissionsByScopeAndSource
     * @param list<SourceExclusion>                   $exclusions
     */
    public function __construct(
        public readonly Emissions $totalEmissions,
        public readonly Emissions $locationBasedElectricityEmissions,
        public readonly Emissions $locationBasedTotalEmissions,
        public readonly int $electricityActivitiesWithoutLocationBased,
        public readonly array $emissionsBySource,
        public readonly array $emissionsByScope,
        public readonly array $emissionsByScopeAndSource,
        public readonly array $exclusions = [],
        public readonly ?StageExclusionAnswer $scope3Exclusion = null,
    ) {}
}
