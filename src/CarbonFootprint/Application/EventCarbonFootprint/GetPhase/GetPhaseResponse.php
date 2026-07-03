<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase;

final class GetPhaseResponse
{
    /** @param list<PhaseSection> $sections */
    public function __construct(
        public readonly array $sections,
    ) {}
}
