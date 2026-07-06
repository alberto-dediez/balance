<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard;

final class EmissionsBreakdown
{
    /**
     * @param array<string, float> $byPhase  co2eq (kg) indexed by Phase value
     * @param array<string, float> $bySource co2eq (kg) indexed by Source value
     */
    public function __construct(
        public readonly array $byPhase,
        public readonly array $bySource,
    ) {}
}
