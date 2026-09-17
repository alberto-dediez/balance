<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

enum BioscoreCatalog: string
{
    case FacilityFuel = 'facility_fuel';
    case TransportFuel = 'transport_fuel';
    case RefrigerantGas = 'refrigerant_gas';
    case ElectricityMarketer = 'electricity_marketer';
}
