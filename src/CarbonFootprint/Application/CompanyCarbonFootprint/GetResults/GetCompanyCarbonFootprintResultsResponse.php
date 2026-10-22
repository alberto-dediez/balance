<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetResults;

use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\Property\Domain\Model\Property\Property;

final class GetCompanyCarbonFootprintResultsResponse
{
    /**
     * @param array<string, Emissions>                $emissionsBySource
     * @param array<string, Emissions>                $emissionsByScope
     * @param array<string, array<string, Emissions>> $emissionsByScopeAndSource
     * @param array<string, Emissions>                $emissionsByPropertyId
     * @param list<Property>                          $properties
     * @param list<SourceExclusion>                   $exclusions
     */
    public function __construct(
        public readonly Emissions $totalEmissions,
        public readonly Emissions $locationBasedElectricityEmissions,
        public readonly array $emissionsBySource,
        public readonly array $emissionsByScope,
        public readonly array $emissionsByScopeAndSource,
        public readonly array $emissionsByPropertyId = [],
        public readonly array $properties = [],
        public readonly array $exclusions = [],
        public readonly ?StageExclusionAnswer $scope3Exclusion = null,
    ) {}
}
