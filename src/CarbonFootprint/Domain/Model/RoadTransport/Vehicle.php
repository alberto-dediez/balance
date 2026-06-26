<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\RoadTransport;

enum Vehicle: string
{
    case PassengerCars = 'passenger_cars';
    case Vans          = 'vans';
    case Trucks        = 'trucks';
    case Mopeds        = 'mopeds';
    case Motorcycles   = 'motorcycles';
    case Buses         = 'buses';
}
