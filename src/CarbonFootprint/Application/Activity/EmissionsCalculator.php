<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\UnitConverter;

final class EmissionsCalculator
{
    public function __construct(private readonly UnitConverter $converter) {}

    public function calculate(EmissionFactor $factor, Quantity $quantity): Emissions
    {
        $converted = $this->converter->convert($quantity, $factor->unit());

        return new Emissions(
            co2eq: (string) ((float) $factor->co2eq() * $converted->value()),
            co2: (string) ((float) $factor->co2() * $converted->value()),
            ch4: (string) ((float) $factor->ch4() * $converted->value()),
            n2o: (string) ((float) $factor->n2o() * $converted->value()),
        );
    }
}
