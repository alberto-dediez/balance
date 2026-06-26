<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible;

use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Unit;

class CombustibleMother
{
    public static function registeredCombustible(): Combustible
    {
        return self::fromName('combustible-name');
    }

    public static function fromName(string $name): Combustible
    {
        return self::from(
            name: $name,
            id: (string) CombustibleId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Combustible
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        Unit $unit = Unit::Kilogram,
    ): Combustible {
        $combustible = new Combustible(
            id: new CombustibleId($id),
        );
        $combustible->register($name, $unit);

        return $combustible;
    }

    public static function random(): Combustible
    {
        return self::from(
            name: 'combustible-' . rand(100, 999),
            id: (string) CombustibleId::generate(),
        );
    }
}
