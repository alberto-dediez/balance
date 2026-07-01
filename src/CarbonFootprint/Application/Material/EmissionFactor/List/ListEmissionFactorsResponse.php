<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Material\Material;

class ListEmissionFactorsResponse
{
    /** @param array<Material> $materials */
    public function __construct(
        public array $materials,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
