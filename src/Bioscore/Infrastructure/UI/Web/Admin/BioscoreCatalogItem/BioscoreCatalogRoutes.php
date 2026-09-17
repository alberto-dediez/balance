<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\UI\Web\Admin\BioscoreCatalogItem;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;

final class BioscoreCatalogRoutes
{
    public static function routeName(BioscoreCatalog $catalog): string
    {
        return match ($catalog) {
            BioscoreCatalog::FacilityFuel => 'admin_bioscore_facility_fuels',
            BioscoreCatalog::TransportFuel => 'admin_bioscore_transport_fuels',
            BioscoreCatalog::RefrigerantGas => 'admin_bioscore_refrigerant_gases',
            BioscoreCatalog::ElectricityMarketer => 'admin_bioscore_electricity_marketers',
        };
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        $routes = [];

        foreach (BioscoreCatalog::cases() as $catalog) {
            $routes[$catalog->value] = self::routeName($catalog);
        }

        return $routes;
    }
}
