<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\RoadTransportActivity;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class RoadTransportActivityMother
{
    public static function registered(?AccountId $accountId = null): RoadTransportActivity
    {
        return RoadTransportActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            roadTransportId: RoadTransportId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilometer, 500.0),
        );
    }
}
