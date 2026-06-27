<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Waste;

use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;

class WasteMother
{
    public static function registeredWaste(): Waste
    {
        return self::fromName('waste-name');
    }

    public static function fromName(string $name): Waste
    {
        return self::from(
            name: $name,
            id: (string) WasteId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Waste
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
        float $density = 1.0,
    ): Waste {
        $waste = new Waste(
            id: new WasteId($id),
        );
        $waste->register($name, $unit, $density);

        return $waste;
    }

    public static function random(): Waste
    {
        return self::from(
            name: 'waste-' . rand(100, 999),
            id: (string) WasteId::generate(),
        );
    }
}
