<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;

final class StageSections
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
