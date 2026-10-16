<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Transport;

enum Vehicle: string
{
    case PassengerCars = 'passenger_cars';
    case Vans          = 'vans';
    case Trucks        = 'trucks';
    case Mopeds        = 'mopeds';
    case Motorcycles   = 'motorcycles';
    case Buses         = 'buses';
    case FreightTrain  = 'freight_train';
    case ContainerShip = 'container_ship';
    case Barge         = 'barge';
    case AirFreight    = 'air_freight';

    public function mode(): TransportMode
    {
        return match ($this) {
            self::PassengerCars,
            self::Vans,
            self::Trucks,
            self::Mopeds,
            self::Motorcycles,
            self::Buses         => TransportMode::Road,
            self::FreightTrain  => TransportMode::Rail,
            self::ContainerShip,
            self::Barge         => TransportMode::Sea,
            self::AirFreight    => TransportMode::Air,
        };
    }

    /** @return list<self> */
    public static function ofMode(TransportMode $mode): array
    {
        return array_values(array_filter(self::cases(), static fn(self $vehicle): bool => $vehicle->mode() === $mode));
    }
}
