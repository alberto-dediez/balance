<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Transport\Transport;

class ListEmissionFactorsResponse
{
    /** @param array<Transport> $transports */
    public function __construct(
        public array $transports,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
