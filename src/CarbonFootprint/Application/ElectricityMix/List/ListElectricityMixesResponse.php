<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\List;

class ListElectricityMixesResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix> $electricityMixes */
    public function __construct(public array $electricityMixes = [], public int $totalResults = 0) {}
}
