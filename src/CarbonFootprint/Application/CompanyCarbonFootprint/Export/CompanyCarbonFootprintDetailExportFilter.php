<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\Export;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;

class CompanyCarbonFootprintDetailExportFilter
{
    public function __construct(
        public ?CompanyCarbonFootprintId $companyCarbonFootprintId = null,
    ) {}
}
