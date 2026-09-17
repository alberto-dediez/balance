<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Domain\Model\BioscoreConsumption;

use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumption;

class BioscoreConsumptionMother
{
    public static function facilityFuel(int $itemId, string $name, float $consumption, string $unit = ''): BioscoreConsumption
    {
        return new BioscoreConsumption($itemId, $name, $unit, true, false, $consumption, 0.0);
    }

    public static function transportFuel(int $itemId, string $name, float $consumption, string $unit = ''): BioscoreConsumption
    {
        return new BioscoreConsumption($itemId, $name, $unit, false, true, $consumption, 0.0);
    }

    public static function dualUseFuel(int $itemId, string $name, float $consumption, string $unit = ''): BioscoreConsumption
    {
        return new BioscoreConsumption($itemId, $name, $unit, true, true, $consumption, 0.0);
    }

    public static function fuelWithoutUse(int $itemId, string $name, float $consumption, string $unit = ''): BioscoreConsumption
    {
        return new BioscoreConsumption($itemId, $name, $unit, false, false, $consumption, 0.0);
    }

    public static function refrigerantGas(int $itemId, string $name, float $consumption, string $unit = 'kg'): BioscoreConsumption
    {
        return new BioscoreConsumption($itemId, $name, $unit, null, null, $consumption, 0.0);
    }

    public static function electricity(int $itemId, string $name, float $consumption, string $unit = 'kWh'): BioscoreConsumption
    {
        return new BioscoreConsumption($itemId, $name, $unit, null, null, $consumption, 0.0);
    }
}
