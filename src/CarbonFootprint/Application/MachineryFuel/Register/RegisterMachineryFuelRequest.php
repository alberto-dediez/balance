<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Register;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterMachineryFuelRequest implements Command
{
    public function __construct(
        public string $name = '',
        public Unit $unit = Unit::Liter,
        public MachineryType $machineryType = MachineryType::Agricultural,
    ) {}
}
