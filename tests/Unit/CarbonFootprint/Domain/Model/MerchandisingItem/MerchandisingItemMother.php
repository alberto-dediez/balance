<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemCategory;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Unit;

class MerchandisingItemMother
{
    public static function registeredMerchandisingItem(): MerchandisingItem
    {
        return self::fromName('merchandising-item-name');
    }

    public static function fromName(string $name): MerchandisingItem
    {
        return self::from(
            name: $name,
            id: (string) MerchandisingItemId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): MerchandisingItem
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        MerchandisingItemCategory $category = MerchandisingItemCategory::Merchandising,
        Unit $unit = Unit::Kilogram,
        ?string $unitWeight = null,
    ): MerchandisingItem {
        $merchandisingItem = new MerchandisingItem(
            id: new MerchandisingItemId($id),
        );
        $merchandisingItem->register($name, $category, $unit, $unitWeight);

        return $merchandisingItem;
    }

    public static function random(): MerchandisingItem
    {
        return self::from(
            name: 'merchandising-item-' . rand(100, 999),
            id: (string) MerchandisingItemId::generate(),
        );
    }
}
