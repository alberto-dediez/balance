<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;

class ListEmissionFactorsResponse
{
    /** @param array<MachineryFuel> $machineryFuels */
    public function __construct(
        public array $machineryFuels,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
