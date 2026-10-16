<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\Unit;

class EconomicSectorMother
{
    public static function registeredEconomicSector(): EconomicSector
    {
        return self::fromName('economic-sector-name');
    }

    public static function fromName(string $name): EconomicSector
    {
        return self::from(
            name: $name,
            id: (string) EconomicSectorId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): EconomicSector
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        Unit $unit = Unit::Euro,
        ?string $classificationCode = null,
    ): EconomicSector {
        $economicSector = new EconomicSector(
            id: new EconomicSectorId($id),
        );
        $economicSector->register($name, $unit, $classificationCode);

        return $economicSector;
    }

    public static function withCode(string $code): EconomicSector
    {
        return self::from(
            name: 'economic-sector-' . $code,
            id: (string) EconomicSectorId::generate(),
            classificationCode: $code,
        );
    }

    public static function random(): EconomicSector
    {
        return self::from(
            name: 'economic-sector-' . rand(100, 999),
            id: (string) EconomicSectorId::generate(),
        );
    }
}
