<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;

class BioscoreCatalogItemMother
{
    public static function registered(
        BioscoreCatalog $catalog = BioscoreCatalog::FacilityFuel,
        int $bioscoreId = 1,
        string $name = 'Gas natural',
        string $unit = 'm3',
        ?string $id = null,
    ): BioscoreCatalogItem {
        $item = new BioscoreCatalogItem(
            id: new BioscoreCatalogItemId($id ?? (string) BioscoreCatalogItemId::generate()),
        );

        $item->register($catalog, $bioscoreId, $name, $unit, new \DateTimeImmutable('NOW'));

        return $item;
    }

    public static function mapped(
        string $targetId,
        bool $automatic = false,
        BioscoreCatalog $catalog = BioscoreCatalog::FacilityFuel,
        int $bioscoreId = 1,
        string $name = 'Gas natural',
        string $unit = 'm3',
        ?string $id = null,
    ): BioscoreCatalogItem {
        $item = self::registered($catalog, $bioscoreId, $name, $unit, $id);

        $item->map($targetId, $automatic);

        return $item;
    }

    public static function ignored(
        BioscoreCatalog $catalog = BioscoreCatalog::FacilityFuel,
        int $bioscoreId = 1,
        string $name = 'Gas natural',
        string $unit = 'm3',
        ?string $id = null,
    ): BioscoreCatalogItem {
        $item = self::registered($catalog, $bioscoreId, $name, $unit, $id);

        $item->ignore();

        return $item;
    }

    public static function withdrawn(
        BioscoreCatalog $catalog = BioscoreCatalog::FacilityFuel,
        int $bioscoreId = 1,
        string $name = 'Gas natural',
        string $unit = 'm3',
        ?string $id = null,
    ): BioscoreCatalogItem {
        $item = self::registered($catalog, $bioscoreId, $name, $unit, $id);

        $item->withdraw(new \DateTimeImmutable('NOW'));

        return $item;
    }

    public static function random(): BioscoreCatalogItem
    {
        return self::registered(
            bioscoreId: random_int(100, 999999),
            name: 'bioscore-item-' . random_int(100, 999),
        );
    }
}
