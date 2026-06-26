<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;

class ElectricityMarketerMother
{
    public static function registeredElectricityMarketer(): ElectricityMarketer
    {
        return self::fromName('electricity-marketer-name');
    }

    public static function fromName(string $name): ElectricityMarketer
    {
        return self::from(
            name: $name,
            id: (string) ElectricityMarketerId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): ElectricityMarketer
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
    ): ElectricityMarketer {
        $electricityMarketer = new ElectricityMarketer(
            id: new ElectricityMarketerId($id),
        );
        $electricityMarketer->register($name);

        return $electricityMarketer;
    }

    public static function random(): ElectricityMarketer
    {
        return self::from(
            name: 'electricity-marketer-' . rand(100, 999),
            id: (string) ElectricityMarketerId::generate(),
        );
    }
}
