<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivity;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class MerchandisingItemActivityMother
{
    public static function registered(?AccountId $accountId = null): MerchandisingItemActivity
    {
        return MerchandisingItemActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            merchandisingItemId: MerchandisingItemId::generate(),
            condition: ItemCondition::Recycled,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Piece, 100.0),
        );
    }
}
