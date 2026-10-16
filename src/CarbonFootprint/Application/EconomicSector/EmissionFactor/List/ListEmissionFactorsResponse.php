<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

class ListEmissionFactorsResponse
{
    /** @param array<EconomicSector> $economicSectors */
    public function __construct(
        public array $economicSectors,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
