<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintExclusion;

final class GetPhaseResponse
{
    /**
     * @param list<PhaseSection>                           $sections
     * @param array<string, EventCarbonFootprintExclusion> $exclusions
     */
    public function __construct(
        public readonly array $sections,
        public readonly array $exclusions = [],
    ) {}
}
