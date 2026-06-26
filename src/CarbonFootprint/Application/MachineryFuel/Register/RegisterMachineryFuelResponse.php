<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Register;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterMachineryFuelResponse implements CommandResponse
{
    public function __construct(
        public MachineryFuelId $machineryFuelId,
        public string $name,
    ) {}
}
