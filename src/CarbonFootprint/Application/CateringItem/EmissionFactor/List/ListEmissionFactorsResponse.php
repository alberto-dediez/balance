<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;

class ListEmissionFactorsResponse
{
    /** @param array<CateringItem> $cateringItems */
    public function __construct(
        public array $cateringItems,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
    ) {}
}
