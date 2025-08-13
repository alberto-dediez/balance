<?php

namespace App\Tests\Unit\Waste\Domain\Model\Waste;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;

class WasteMother
{
    public static function registeredWaste()
    {   
        return self::fromName('name');
    }

    public static function fromName($name)
    {
        return self::from(
            name: $name,
            isDangerous: rand(0, 1) == 1,
            id: (string) WasteId::generate()
        );
    }
    
    public static function fromIsDangerous(bool $isDangerous)
    {
        return self::from(
            name: 'waste-' . rand(100, 999),
            isDangerous: $isDangerous,
            id: (string) WasteId::generate()
        );
    }

    public static function fromNameAndId(string $name, string $id)
    {
        return self::from(
            name: $name, 
            isDangerous: rand(0, 1) == 1,
            id: $id
        );
    }

    public static function fromDensityAndId(string $density, string $id)
    {
        return self::from(
            name: 'waste-' . rand(100, 999),
            isDangerous: rand(0, 1) == 1,
            density: $density,
            id: $id
        );
    }

    public static function from(
        string $name, 
        bool $isDangerous,
        string $id,
        string $density = '1.00', 
        string $color = '#c3d300',
    ) {
        $waste = new Waste(
            id: new WasteId($id)
        );
        $waste->register($name, $isDangerous, $density, $color);

        return $waste;
    }

    public static function random()
    {
        return self::from(
            name: 'waste-' . rand(100, 999),
            isDangerous: rand(0, 1) == 1,
            density: rand(1, 100) / 100,
            id: (string) WasteId::generate()
        );
    }
}