<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CapitalGood;

enum CapitalGoodCategory: string
{
    case Renovations           = 'renovations';
    case FurnitureAndEquipment = 'furniture_and_equipment';
    case VehiclesAndMachinery  = 'vehicles_and_machinery';
    case Others                = 'others';
}
