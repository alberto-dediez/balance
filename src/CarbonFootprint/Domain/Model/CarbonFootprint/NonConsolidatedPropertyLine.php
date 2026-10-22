<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;

final readonly class NonConsolidatedPropertyLine
{
    public function __construct(
        public Source $source,
        public PropertyId $propertyId,
        public ?PropertyCarbonFootprintId $propertyCarbonFootprintId,
        public Emissions $calculatedEmissions,
        public ?string $manualCo2eq,
        public Emissions $emissions,
    ) {}
}
