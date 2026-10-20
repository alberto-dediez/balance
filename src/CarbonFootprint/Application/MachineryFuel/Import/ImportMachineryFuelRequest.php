<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Import;

use App\Shared\Application\CommandBus\Command;

class ImportMachineryFuelRequest implements Command
{
    public function __construct(
        public ?string $machineryFuel = null,
        public ?string $name = null,
        public ?string $unit = null,
        public ?string $machineryType = null,
        public ?string $year = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
        public ?string $wellToTankCo2eq = null,
        public ?string $wellToTankCo2 = null,
        public ?string $wellToTankCh4 = null,
        public ?string $wellToTankN2o = null,
        public ?string $wellToTankSource = null,
        public ?string $emissionFactorGroup = null,
    ) {}
}
