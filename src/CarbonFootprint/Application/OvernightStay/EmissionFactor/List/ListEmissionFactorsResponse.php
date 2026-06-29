<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;

class ListEmissionFactorsResponse
{
    /** @param array<OvernightStay> $overnightStays */
    public function __construct(
        public array $overnightStays,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
    ) {}
}
