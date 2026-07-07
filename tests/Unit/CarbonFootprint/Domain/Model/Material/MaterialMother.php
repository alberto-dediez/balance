<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Unit;

class MaterialMother
{
    public static function registeredMaterial(): Material
    {
        return self::fromName('material-name');
    }

    public static function fromName(string $name): Material
    {
        return self::from(
            name: $name,
            id: (string) MaterialId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Material
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        MaterialCategory $category = MaterialCategory::Metal,
        Unit $unit = Unit::Kilogram,
    ): Material {
        $material = new Material(
            id: new MaterialId($id),
        );
        $material->register($name, $category, $unit);

        return $material;
    }

    public static function random(): Material
    {
        return self::from(
            name: 'material-' . rand(100, 999),
            id: (string) MaterialId::generate(),
        );
    }
}
