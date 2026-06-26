<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Remove;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\Shared\Application\CommandBus\Command;

class RemoveMachineryFuelRequest implements Command
{
    public function __construct(public MachineryFuelId $machineryFuelId) {}
}
