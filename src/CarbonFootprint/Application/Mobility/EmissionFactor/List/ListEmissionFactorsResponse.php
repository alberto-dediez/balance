<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;

class ListEmissionFactorsResponse
{
    /** @param array<Mobility> $mobilities */
    public function __construct(
        public array $mobilities,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
