<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Import;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportMachineryFuelResponse implements CommandResponse
{
    public function __construct(public MachineryFuelId $machineryFuelId) {}
}
