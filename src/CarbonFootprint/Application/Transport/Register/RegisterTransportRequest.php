<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\Register;

use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterTransportRequest implements Command
{
    public function __construct(
        public string $name = '',
        public Unit $unit = Unit::Kilogram,
        public Vehicle $vehicle = Vehicle::PassengerCars,
    ) {}
}
