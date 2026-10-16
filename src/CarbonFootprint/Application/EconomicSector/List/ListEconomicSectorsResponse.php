<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\List;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;

class ListEconomicSectorsResponse
{
    /** @param array<EconomicSector> $economicSectors */
    public function __construct(public array $economicSectors = [], public int $totalResults = 0) {}
}
