<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Unit;

class MobilityMother
{
    public static function registeredMobility(): Mobility
    {
        return self::fromName('mobility-name');
    }

    public static function fromName(string $name): Mobility
    {
        return self::from(
            name: $name,
            id: (string) MobilityId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Mobility
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function fromNameIdAndTravelMode(string $name, string $id, TravelMode $travelMode): Mobility
    {
        return self::from(
            name: $name,
            id: $id,
            travelMode: $travelMode,
        );
    }

    public static function from(
        string $name,
        string $id,
        Unit $unit = Unit::Kilometer,
        ?TravelMode $travelMode = null,
    ): Mobility {
        $mobility = new Mobility(
            id: new MobilityId($id),
        );
        $mobility->register($name, $unit, $travelMode);

        return $mobility;
    }

    public static function random(): Mobility
    {
        return self::from(
            name: 'mobility-' . rand(100, 999),
            id: (string) MobilityId::generate(),
        );
    }
}
