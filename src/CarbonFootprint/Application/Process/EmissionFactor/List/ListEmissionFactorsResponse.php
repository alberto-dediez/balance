<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Process\Process;

class ListEmissionFactorsResponse
{
    /** @param array<Process> $processes */
    public function __construct(
        public array $processes,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
