<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicatorValue;

final readonly class TimeSeriesPoint
{
    /**
     * @param array<string, float>                   $byScopeKg
     * @param array<string, IntensityIndicatorValue> $indicators
     */
    public function __construct(
        public int $year,
        public float $totalKg,
        public array $byScopeKg,
        public array $indicators,
        public bool $isBaseYear,
    ) {}
}
