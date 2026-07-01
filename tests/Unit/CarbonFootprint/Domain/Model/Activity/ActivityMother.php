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
    public static function emissionFactor(?EmissionFactorGroupId $groupId = null): EmissionFactor
    {
        // Defaults to the well-known "default" group sentinel. Tests that need to assert
        // the group actually propagated through the request/selector should pass an explicit
        // $groupId here and reuse the same value when building the Register/Update request.
        return new EmissionFactor('1.5', '1.5', '0', '0', 2024, $groupId ?? EmissionFactorGroupId::default(), Unit::Kilogram);
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
