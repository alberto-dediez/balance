<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

class CombustibleActivityMother
{
    public static function registered(?AccountId $accountId = null): CombustibleActivity
    {
        return CombustibleActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            combustibleId: CombustibleId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Liter, 200.0),
        );
    }

    public static function registeredWithWellToTank(?AccountId $accountId = null): CombustibleActivity
    {
        return CombustibleActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            combustibleId: CombustibleId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Liter, 200.0),
            wellToTankEmissionFactor: ActivityMother::wellToTankEmissionFactor(),
            wellToTankEmissions: ActivityMother::wellToTankEmissions(),
        );
    }
}
