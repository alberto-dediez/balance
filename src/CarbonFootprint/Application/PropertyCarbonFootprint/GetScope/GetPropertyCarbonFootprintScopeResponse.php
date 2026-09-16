<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;

final class GetPropertyCarbonFootprintScopeResponse
{
    /**
     * @param list<StageSection>             $sections
     * @param array<string, SourceExclusion> $exclusions
     */
    public function __construct(
        public readonly array $sections,
        public readonly array $exclusions = [],
    ) {}
}
