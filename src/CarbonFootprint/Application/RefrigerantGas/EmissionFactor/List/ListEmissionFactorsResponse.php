<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;

class ListEmissionFactorsResponse
{
    /** @param array<RefrigerantGas> $refrigerantGases */
    public function __construct(
        public array $refrigerantGases,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
