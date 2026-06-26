<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Register;

use App\CarbonFootprint\Domain\Model\RoadTransport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterRoadTransportRequest implements Command
{
    public function __construct(
        public string $name = '',
        public Unit $unit = Unit::Kilogram,
        public Vehicle $vehicle = Vehicle::PassengerCars,
    ) {}
}
