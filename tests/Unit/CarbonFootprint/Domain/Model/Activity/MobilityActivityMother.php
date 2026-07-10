<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\MobilityActivity;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class MobilityActivityMother
{
    public static function registered(?AccountId $accountId = null): MobilityActivity
    {
        return MobilityActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            mobilityId: MobilityId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilometer, 300.0),
        );
    }
}
