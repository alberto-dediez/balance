<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;

class ListEmissionFactorsResponse
{
    /** @param array<ElectricityMarketer> $electricityMarketers */
    public function __construct(
        public array $electricityMarketers,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
    ) {}
}
