<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivity;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class TransportActivityMother
{
    public static function registered(?AccountId $accountId = null): TransportActivity
    {
        return TransportActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            transportId: TransportId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilometer, 500.0),
        );
    }

    public static function registeredInTonneKilometre(?AccountId $accountId = null): TransportActivity
    {
        return TransportActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            transportId: TransportId::generate(),
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::TonneKilometre),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::TonneKilometre, 875.0),
            weight: new Quantity(2500.0, Unit::Kilogram),
            distanceKm: 350.0,
        );
    }
}
