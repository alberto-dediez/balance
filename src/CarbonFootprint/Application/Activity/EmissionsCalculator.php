<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;

final class EmissionsCalculator
{
    public function calculate(EmissionFactor $factor, Quantity $quantity): Emissions
    {
        return new Emissions(
            co2eq: (string) ((float) $factor->co2eq() * $quantity->value()),
            co2: (string) ((float) $factor->co2() * $quantity->value()),
            ch4: (string) ((float) $factor->ch4() * $quantity->value()),
            n2o: (string) ((float) $factor->n2o() * $quantity->value()),
        );
    }
}
