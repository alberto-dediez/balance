<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\CateringItemActivity;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class CateringItemActivityMother
{
    public static function registered(?AccountId $accountId = null): CateringItemActivity
    {
        return CateringItemActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            cateringItemId: CateringItemId::generate(),
            condition: ItemCondition::New,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 5.0),
        );
    }
}
