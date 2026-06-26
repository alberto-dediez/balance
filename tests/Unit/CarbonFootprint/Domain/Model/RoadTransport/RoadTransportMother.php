<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;

class RoadTransportMother
{
    public static function registeredRoadTransport(): RoadTransport
    {
        return self::fromName('road-transport-name');
    }

    public static function fromName(string $name): RoadTransport
    {
        return self::from(
            name: $name,
            id: (string) RoadTransportId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): RoadTransport
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        Unit $unit = Unit::Kilogram,
        Vehicle $vehicle = Vehicle::PassengerCars,
    ): RoadTransport {
        $roadTransport = new RoadTransport(
            id: new RoadTransportId($id),
        );
        $roadTransport->register($name, $unit, $vehicle);

        return $roadTransport;
    }

    public static function random(): RoadTransport
    {
        return self::from(
            name: 'road-transport-' . rand(100, 999),
            id: (string) RoadTransportId::generate(),
        );
    }
}
