<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\Emissions;

final class TripCalculationsAggregation
{
    /** @param array<string, float> $kmByMobilityId */
    public function __construct(
        public readonly array $kmByMobilityId,
        public readonly float $nights,
        public readonly int $calculations,
        public readonly Emissions $totalEmissions,
        public readonly Emissions $averageEmissions,
    ) {}
}
