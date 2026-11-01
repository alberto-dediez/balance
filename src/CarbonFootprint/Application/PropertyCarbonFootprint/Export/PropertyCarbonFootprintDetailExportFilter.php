<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\Export;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;

class PropertyCarbonFootprintDetailExportFilter
{
    public function __construct(
        public ?PropertyCarbonFootprintId $propertyCarbonFootprintId = null,
    ) {}
}
