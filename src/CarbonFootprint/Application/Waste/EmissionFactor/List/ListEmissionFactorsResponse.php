<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Waste\Waste;

class ListEmissionFactorsResponse
{
    /** @param array<Waste> $wastes */
    public function __construct(
        public array $wastes,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
