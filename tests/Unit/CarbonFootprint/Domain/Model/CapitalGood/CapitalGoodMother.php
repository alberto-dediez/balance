<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodCategory;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\Unit;

class CapitalGoodMother
{
    public static function registeredCapitalGood(): CapitalGood
    {
        return self::fromName('capital-good-name');
    }

    public static function fromName(string $name): CapitalGood
    {
        return self::from(
            name: $name,
            id: (string) CapitalGoodId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): CapitalGood
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        CapitalGoodCategory $category = CapitalGoodCategory::Renovations,
        Unit $unit = Unit::Euro,
    ): CapitalGood {
        $capitalGood = new CapitalGood(
            id: new CapitalGoodId($id),
        );
        $capitalGood->register($name, $category, $unit);

        return $capitalGood;
    }

    public static function random(): CapitalGood
    {
        return self::from(
            name: 'capital-good-' . rand(100, 999),
            id: (string) CapitalGoodId::generate(),
        );
    }
}
