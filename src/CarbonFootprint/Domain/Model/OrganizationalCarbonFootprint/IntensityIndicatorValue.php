<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

final readonly class IntensityIndicatorValue
{
    public function __construct(
        public IntensityIndicator $indicator,
        public float $denominator,
        public float $kgCo2eqPerUnit,
    ) {}
}
