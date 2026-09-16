<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class RefrigerantGasActivityMother
{
    public static function registered(?AccountId $accountId = null): RefrigerantGasActivity
    {
        return RefrigerantGasActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            refrigerantGasId: RefrigerantGasId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 2.0),
            source: 'Sala de máquinas',
        );
    }
}
