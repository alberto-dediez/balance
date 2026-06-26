<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Remove;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveMachineryFuelResponse implements CommandResponse
{
    public function __construct(public MachineryFuelId $machineryFuelId) {}
}
