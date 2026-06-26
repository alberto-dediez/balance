<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Update;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateMachineryFuelRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public MachineryFuelId $machineryFuelId,
        public string $name,
        public Unit $unit,
        public MachineryType $machineryType,
        public array $emissionFactors = [],
    ) {}
}
