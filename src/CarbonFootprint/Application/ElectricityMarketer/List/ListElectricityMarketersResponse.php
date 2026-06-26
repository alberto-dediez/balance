<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\List;

class ListElectricityMarketersResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer> $electricityMarketers */
    public function __construct(public array $electricityMarketers = [], public int $totalResults = 0) {}
}
