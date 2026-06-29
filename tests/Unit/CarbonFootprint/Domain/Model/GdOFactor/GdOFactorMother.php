<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;

class GdOFactorMother
{
    public static function registeredGdOFactor(): GdOFactor
    {
        return self::fromName('gdo-factor-name');
    }

    public static function fromName(string $name): GdOFactor
    {
        return self::from(
            name: $name,
            id: (string) GdOFactorId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): GdOFactor
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
    ): GdOFactor {
        $gdOFactor = new GdOFactor(
            id: new GdOFactorId($id),
        );
        $gdOFactor->register($name);

        return $gdOFactor;
    }

    public static function random(): GdOFactor
    {
        return self::from(
            name: 'gdo-factor-' . rand(100, 999),
            id: (string) GdOFactorId::generate(),
        );
    }
}
