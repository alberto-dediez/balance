<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\CreateYear;

use App\Shared\Application\CommandBus\Command;

class CreateEmissionFactorsYearRequest implements Command
{
    public function __construct(
        public int $sourceYear,
        public ?int $targetYear = null,
    ) {}
}
