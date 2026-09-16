<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;

final class GetPhaseResponse
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
