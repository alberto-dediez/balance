<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\EmissionFactor\Update;

use App\CarbonFootprint\Application\ElectricityMix\EmissionFactor\EmissionFactorRow;
use App\Shared\Application\CommandBus\Command;

class UpdateEmissionFactorsRequest implements Command
{
    /** @param EmissionFactorRow[] $rows */
    public function __construct(
        public int $year,
        public array $rows = [],
    ) {}
}
