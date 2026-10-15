<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivity;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class CapitalGoodActivityMother
{
    public static function registered(?AccountId $accountId = null, ?int $acquisitionYear = null): CapitalGoodActivity
    {
        return CapitalGoodActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            capitalGoodId: CapitalGoodId::generate(),
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::Euro),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Euro, 100.0),
            acquisitionYear: $acquisitionYear,
        );
    }
}
