<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\CarbonFootprint\Domain\Model\Unit;

final class BioscoreUnitParser
{
    public static function parse(string $rawUnit): ?Unit
    {
        $value = rtrim(mb_strtolower(trim($rawUnit)), '.');

        return match ($value) {
            'kg', 'kgs', 'kilogramo', 'kilogramos' => Unit::Kilogram,
            'g', 'gramo', 'gramos' => Unit::Gram,
            't', 'tn', 'tonelada', 'toneladas' => Unit::Tonne,
            'l', 'lt', 'lts', 'litro', 'litros' => Unit::Liter,
            'm3', 'm³', 'metro cubico', 'metros cubicos' => Unit::CubicMeter,
            'kwh' => Unit::KWh,
            'kwhpcs', 'kwh pcs' => Unit::KWhPCS,
            default => null,
        };
    }

    public static function isCompatible(string $rawUnit, Unit $targetUnit): bool
    {
        if ('' === trim($rawUnit)) {
            return true;
        }

        $unit = self::parse($rawUnit);

        if (null === $unit) {
            return false;
        }

        return $unit->magnitude() === $targetUnit->magnitude();
    }
}
