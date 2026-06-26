<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemCategory;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\Unit;

class CateringItemMother
{
    public static function registeredCateringItem(): CateringItem
    {
        return self::fromName('catering-item-name');
    }

    public static function fromName(string $name): CateringItem
    {
        return self::from(
            name: $name,
            id: (string) CateringItemId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): CateringItem
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        CateringItemCategory $category = CateringItemCategory::Breakfast,
        Unit $unit = Unit::Kilogram,
        ?string $unitWeight = null,
    ): CateringItem {
        $cateringItem = new CateringItem(
            id: new CateringItemId($id),
        );
        $cateringItem->register($name, $category, $unit, $unitWeight);

        return $cateringItem;
    }

    public static function random(): CateringItem
    {
        return self::from(
            name: 'catering-item-' . rand(100, 999),
            id: (string) CateringItemId::generate(),
        );
    }
}
