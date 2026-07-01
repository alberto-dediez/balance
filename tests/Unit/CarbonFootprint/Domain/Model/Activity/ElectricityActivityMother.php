<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Unit;

class ElectricityActivityMother
{
    public static function registered(): ElectricityActivity
    {
        return ElectricityActivity::register(
            id: ActivityId::generate(),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: GdOFactorId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
        );
    }
}
