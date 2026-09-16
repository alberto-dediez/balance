<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\GetResults;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;

final class GetPropertyCarbonFootprintResultsRequest
{
    public function __construct(
        public readonly PropertyCarbonFootprintId $propertyCarbonFootprintId,
    ) {}
}
