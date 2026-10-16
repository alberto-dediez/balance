<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;

class TransportMother
{
    public static function registeredTransport(): Transport
    {
        return self::fromName('transport-name');
    }

    public static function fromName(string $name): Transport
    {
        return self::from(
            name: $name,
            id: (string) TransportId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Transport
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
    ): Transport {
        $transport = new Transport(
            id: new TransportId($id),
        );
        $transport->register($name, $unit, $vehicle);

        return $transport;
    }

    public static function freightTrainInTonneKilometre(string $name, string $id): Transport
    {
        return self::from(
            name: $name,
            id: $id,
            unit: Unit::TonneKilometre,
            vehicle: Vehicle::FreightTrain,
        );
    }

    public static function random(): Transport
    {
        return self::from(
            name: 'transport-' . rand(100, 999),
            id: (string) TransportId::generate(),
        );
    }
}
