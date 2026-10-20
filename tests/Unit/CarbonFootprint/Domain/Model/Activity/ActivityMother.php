<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;

class ActivityMother
{
    public static function emissionFactor(?EmissionFactorGroupId $groupId = null): EmissionFactor
    {
        return new EmissionFactor('1.5', '1.5', '0', '0', 2024, $groupId ?? EmissionFactorGroupMother::defaultId(), Unit::Kilogram);
    }

    public static function wellToTankEmissionFactor(?EmissionFactorGroupId $groupId = null): EmissionFactor
    {
        return new EmissionFactor('0.61', '0.61', '0', '0', 2024, $groupId ?? EmissionFactorGroupMother::defaultId(), Unit::Kilogram, 'DEFRA');
    }

    public static function wellToTankEmissions(): Emissions
    {
        return new Emissions('122.0', '122.0', '0', '0');
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
