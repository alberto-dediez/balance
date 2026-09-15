<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

class ListEmissionFactorsResponse
{
    /** @param array<ElectricityMix> $electricityMixes */
    public function __construct(
        public array $electricityMixes,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
