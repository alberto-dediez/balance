<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivity;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Unit;

class MerchandisingItemActivityMother
{
    public static function registered(): MerchandisingItemActivity
    {
        return MerchandisingItemActivity::register(
            id: ActivityId::generate(),
            merchandisingItemId: MerchandisingItemId::generate(),
            condition: ItemCondition::Recycled,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Piece, 100.0),
        );
    }
}
