<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;

class ActivityMother
{
    public static function emissionFactor(): EmissionFactor
    {
        // Uses the temporary well-known default group sentinel so it matches the
        // fallback still used by the Activity selectors (FASE 6 will propagate the
        // real EmissionFactorGroupId from the EventCarbonFootprint/request instead).
        return new EmissionFactor('1.5', '1.5', '0', '0', 2024, EmissionFactorGroupId::default(), Unit::Kilogram);
    }

    public static function emissions(): Emissions
    {
        return new Emissions('15.0', '15.0', '0', '0');
    }

    public static function quantity(Unit $unit = Unit::Kilogram, float $value = 200.0): Quantity
    {
        return new Quantity($value, $unit);
    }
}
