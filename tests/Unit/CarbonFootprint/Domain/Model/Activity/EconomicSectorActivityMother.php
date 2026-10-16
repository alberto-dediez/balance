<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivity;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class EconomicSectorActivityMother
{
    public static function registered(?AccountId $accountId = null): EconomicSectorActivity
    {
        return EconomicSectorActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            economicSectorId: EconomicSectorId::generate(),
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::Euro),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Euro, 100.0),
        );
    }
}
