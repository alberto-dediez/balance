<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\OvernightStayActivity;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class OvernightStayActivityMother
{
    public static function registered(?AccountId $accountId = null, ?int $nights = null, ?int $people = null): OvernightStayActivity
    {
        $quantity = null !== $nights && null !== $people ? (float) ($nights * $people) : 2.0;

        return OvernightStayActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            overnightStayId: OvernightStayId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Pernoctacion, $quantity),
            nights: $nights,
            people: $people,
        );
    }
}
