<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType;
use App\CarbonFootprint\Domain\Model\Unit;

class MachineryFuelMother
{
    public static function registeredMachineryFuel(): MachineryFuel
    {
        return self::fromName('machinery-fuel-name');
    }

    public static function fromName(string $name): MachineryFuel
    {
        return self::from(
            name: $name,
            id: (string) MachineryFuelId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): MachineryFuel
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        Unit $unit = Unit::Liter,
        MachineryType $machineryType = MachineryType::Agricultural,
    ): MachineryFuel {
        $machineryFuel = new MachineryFuel(
            id: new MachineryFuelId($id),
        );
        $machineryFuel->register($name, $unit, $machineryType);

        return $machineryFuel;
    }

    public static function random(): MachineryFuel
    {
        return self::from(
            name: 'machinery-fuel-' . rand(100, 999),
            id: (string) MachineryFuelId::generate(),
        );
    }
}
