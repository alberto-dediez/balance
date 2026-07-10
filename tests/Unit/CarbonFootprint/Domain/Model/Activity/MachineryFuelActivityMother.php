<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivity;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class MachineryFuelActivityMother
{
    public static function registered(?AccountId $accountId = null): MachineryFuelActivity
    {
        return MachineryFuelActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            machineryFuelId: MachineryFuelId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Liter, 50.0),
        );
    }
}
