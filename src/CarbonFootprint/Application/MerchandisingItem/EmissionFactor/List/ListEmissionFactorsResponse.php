<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;

class ListEmissionFactorsResponse
{
    /** @param array<MerchandisingItem> $merchandisingItems */
    public function __construct(
        public array $merchandisingItems,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
    ) {}
}
