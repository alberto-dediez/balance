<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivity;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;

class ReportedEmissionsActivityMother
{
    public static function registered(?AccountId $accountId = null, string $description = 'Reparto 2025', float $kilograms = 3200.0): ReportedEmissionsActivity
    {
        return ReportedEmissionsActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            description: $description,
            emissionFactor: self::identityEmissionFactor(),
            emissions: new Emissions((string) $kilograms, (string) $kilograms, '0', '0'),
            quantity: new Quantity($kilograms, Unit::Kilogram),
        );
    }

    public static function identityEmissionFactor(int $year = 2024): EmissionFactor
    {
        return new EmissionFactor('1', '1', '0', '0', $year, EmissionFactorGroupMother::defaultId(), Unit::Kilogram, 'reported');
    }
}
