<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;

class ListEmissionFactorsResponse
{
    /** @param array<GdOFactor> $gdOFactors */
    public function __construct(
        public array $gdOFactors,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
    ) {}
}
