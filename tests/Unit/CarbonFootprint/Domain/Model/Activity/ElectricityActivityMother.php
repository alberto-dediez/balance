<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;

class ElectricityActivityMother
{
    public static function registered(?AccountId $accountId = null): ElectricityActivity
    {
        return ElectricityActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: GdOFactorId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
        );
    }

    public static function registeredWithLocationBasedResult(?AccountId $accountId = null): ElectricityActivity
    {
        return ElectricityActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: GdOFactorId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
            electricityMixId: ElectricityMixId::generate(),
            locationBasedEmissionFactor: self::locationBasedEmissionFactor(),
            locationBasedEmissions: self::locationBasedEmissions(),
            locationBasedWellToTankEmissionFactor: self::locationBasedWellToTankEmissionFactor(),
            locationBasedWellToTankEmissions: self::locationBasedWellToTankEmissions(),
        );
    }

    public static function registeredWithWellToTank(?AccountId $accountId = null): ElectricityActivity
    {
        return ElectricityActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: GdOFactorId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
            wellToTankEmissionFactor: ActivityMother::wellToTankEmissionFactor(),
            wellToTankEmissions: ActivityMother::wellToTankEmissions(),
        );
    }

    public static function locationBasedEmissionFactor(): EmissionFactor
    {
        return new EmissionFactor('0.18', '0.18', '0', '0', 2024, EmissionFactorGroupMother::defaultId(), Unit::KWh, 'REE');
    }

    public static function locationBasedEmissions(): Emissions
    {
        return new Emissions('180.0', '180.0', '0', '0');
    }

    public static function locationBasedWellToTankEmissionFactor(): EmissionFactor
    {
        return new EmissionFactor('0.03', '0.03', '0', '0', 2024, EmissionFactorGroupMother::defaultId(), Unit::KWh, 'DEFRA');
    }

    public static function locationBasedWellToTankEmissions(): Emissions
    {
        return new Emissions('30.0', '30.0', '0', '0');
    }
}
