<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\List;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;

class ListCarbonFootprintReportsResponse
{
    /** @param array<CarbonFootprintReport> $reports */
    public function __construct(public array $reports = [], public int $totalResults = 0) {}
}
