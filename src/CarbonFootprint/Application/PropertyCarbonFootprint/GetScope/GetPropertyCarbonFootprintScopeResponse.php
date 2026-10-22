<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\DerivedSourceLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\Emissions;

final class GetPropertyCarbonFootprintScopeResponse
{
    /**
     * @param list<StageSection>             $sections
     * @param array<string, SourceExclusion> $exclusions
     * @param array<string, Emissions>       $derivedEmissions
     * @param list<DerivedSourceLine>        $derivedLines
     */
    public function __construct(
        public readonly array $sections,
        public readonly array $exclusions = [],
        public readonly ?StageExclusionAnswer $stageExclusion = null,
        public readonly array $derivedEmissions = [],
        public readonly array $derivedLines = [],
    ) {}
}
