<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\EmissionFactor\Update;

use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\OvernightStayEmissionFactorRow;
use App\Shared\Application\CommandBus\Command;

class UpdateEmissionFactorsRequest implements Command
{
    /** @param OvernightStayEmissionFactorRow[] $rows */
    public function __construct(
        public int $year,
        public array $rows = [],
    ) {}
}
