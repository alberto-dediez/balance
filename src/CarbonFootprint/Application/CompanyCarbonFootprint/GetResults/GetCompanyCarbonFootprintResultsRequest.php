<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetResults;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;

final class GetCompanyCarbonFootprintResultsRequest
{
    public function __construct(
        public readonly CompanyCarbonFootprintId $companyCarbonFootprintId,
    ) {}
}
