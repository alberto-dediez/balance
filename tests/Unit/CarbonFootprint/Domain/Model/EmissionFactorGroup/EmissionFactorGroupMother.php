<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;

class EmissionFactorGroupMother
{
    /** Well-known id used by tests that need a stable, arbitrary EmissionFactorGroupId fixture. */
    private const string DEFAULT_ID = '00000000-0000-0000-0000-000000000001';

    public static function defaultId(): EmissionFactorGroupId
    {
        return new EmissionFactorGroupId(self::DEFAULT_ID);
    }

    public static function registeredEmissionFactorGroup(): EmissionFactorGroup
    {
        return self::fromName('Estándar');
    }

    /** The current AR6/MITECO GWP values, registered with the well-known {@see self::defaultId()} and marked as default. */
    public static function default(): EmissionFactorGroup
    {
        $emissionFactorGroup = self::from(
            name: 'Estándar',
            id: self::DEFAULT_ID,
            gwp: new Gwp(gwpCo2: 1.0, gwpCh4: 27.9, gwpN2O: 273.0),
        );
        $emissionFactorGroup->setAsDefault();

        return $emissionFactorGroup;
    }

    public static function fromName(string $name): EmissionFactorGroup
    {
        return self::from(
            name: $name,
            id: (string) EmissionFactorGroupId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): EmissionFactorGroup
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        Gwp $gwp = new Gwp(),
    ): EmissionFactorGroup {
        $emissionFactorGroup = new EmissionFactorGroup(
            id: new EmissionFactorGroupId($id),
        );
        $emissionFactorGroup->register($name, $gwp);

        return $emissionFactorGroup;
    }

    public static function random(): EmissionFactorGroup
    {
        $suffix = rand(100, 999);

        return self::from(
            name: 'group-' . $suffix,
            id: (string) EmissionFactorGroupId::generate(),
        );
    }
}
