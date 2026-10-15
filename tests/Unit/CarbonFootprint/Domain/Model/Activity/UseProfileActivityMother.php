<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\UseProfileActivity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\Security\Domain\Model\Account\AccountId;

class UseProfileActivityMother
{
    public static function registered(?AccountId $accountId = null): UseProfileActivity
    {
        return UseProfileActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            useProfileId: UseProfileId::generate(),
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::Piece),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Piece, 100.0),
        );
    }
}
