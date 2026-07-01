<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;

class EmissionFactorGroupMother
{
    public static function registeredEmissionFactorGroup(): EmissionFactorGroup
    {
        return self::fromCodeAndName('default', 'Estándar');
    }

    /**
     * The current AR6/MITECO GWP values used across the project.
     */
    public static function default(): EmissionFactorGroup
    {
        return self::from(
            code: 'default',
            name: 'Estándar',
            id: (string) EmissionFactorGroupId::generate(),
            gwp: new Gwp(gwpCo2: 1.0, gwpCh4: 27.9, gwpN2O: 273.0),
        );
    }

    public static function fromCodeAndName(string $code, string $name): EmissionFactorGroup
    {
        return self::from(
            code: $code,
            name: $name,
            id: (string) EmissionFactorGroupId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): EmissionFactorGroup
    {
        return self::from(
            code: 'code-' . $id,
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $code,
        string $name,
        string $id,
        Gwp $gwp = new Gwp(),
    ): EmissionFactorGroup {
        $emissionFactorGroup = new EmissionFactorGroup(
            id: new EmissionFactorGroupId($id),
        );
        $emissionFactorGroup->register($code, $name, $gwp);

        return $emissionFactorGroup;
    }

    public static function random(): EmissionFactorGroup
    {
        $suffix = rand(100, 999);

        return self::from(
            code: 'group-' . $suffix,
            name: 'group-' . $suffix,
            id: (string) EmissionFactorGroupId::generate(),
        );
    }
}
