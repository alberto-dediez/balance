<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi;

use App\Property\Domain\Model\Property\OperatingRegime;

final readonly class PropertyComparisonRow
{
    public function __construct(
        public string $propertyId,
        public string $name,
        public OperatingRegime $operatingRegime,
        public float $totalKg,
        public float $denominator,
        public float $intensity,
    ) {}
}
