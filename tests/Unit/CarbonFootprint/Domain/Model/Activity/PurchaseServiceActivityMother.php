<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivity;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class PurchaseServiceActivityMother
{
    public static function registered(?AccountId $accountId = null): PurchaseServiceActivity
    {
        return PurchaseServiceActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            purchaseServiceId: PurchaseServiceId::generate(),
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::Euro),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Euro, 100.0),
        );
    }
}
