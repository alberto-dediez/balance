<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

final readonly class GasBreakdown
{
    public function __construct(
        public float $co2,
        public float $ch4,
        public float $n2o,
        public float $refrigerants,
        public float $remainder,
        public float $total,
    ) {}

    public static function fromComponents(float $co2, float $ch4, float $n2o, float $refrigerants, float $total): self
    {
        $remainder = round($total - ($co2 + $ch4 + $n2o + $refrigerants), 6);

        return new self(
            co2: $co2,
            ch4: $ch4,
            n2o: $n2o,
            refrigerants: $refrigerants,
            remainder: abs($remainder) < 0.005 ? 0.0 : $remainder,
            total: $total,
        );
    }
}
