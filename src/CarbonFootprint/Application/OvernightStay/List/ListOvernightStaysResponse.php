<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\List;

class ListOvernightStaysResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay> $overnightStays */
    public function __construct(public array $overnightStays = [], public int $totalResults = 0) {}
}
