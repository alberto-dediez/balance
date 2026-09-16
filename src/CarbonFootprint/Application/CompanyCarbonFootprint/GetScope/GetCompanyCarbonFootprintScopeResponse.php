<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;

final class GetCompanyCarbonFootprintScopeResponse
{
    /**
     * @param list<PropertyStageSections>    $blocks
     * @param array<string, SourceExclusion> $exclusions
     */
    public function __construct(
        public readonly array $blocks,
        public readonly array $exclusions = [],
    ) {}
}
