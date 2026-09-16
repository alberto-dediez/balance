<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\List;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;

class ListCompanyCarbonFootprintsResponse
{
    /** @param CompanyCarbonFootprint[] $companyCarbonFootprints */
    public function __construct(public array $companyCarbonFootprints = [], public int $totalResults = 0) {}
}
