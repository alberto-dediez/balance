<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivity;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Unit;

class MachineryFuelActivityMother
{
    public static function registered(): MachineryFuelActivity
    {
        return MachineryFuelActivity::register(
            id: ActivityId::generate(),
            machineryFuelId: MachineryFuelId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Liter, 50.0),
        );
    }
}
