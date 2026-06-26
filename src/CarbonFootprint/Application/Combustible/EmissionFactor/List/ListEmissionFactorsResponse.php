<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\Combustible\Combustible;

class ListEmissionFactorsResponse
{
    /** @param array<Combustible> $combustibles */
    public function __construct(
        public array $combustibles,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
    ) {}
}
