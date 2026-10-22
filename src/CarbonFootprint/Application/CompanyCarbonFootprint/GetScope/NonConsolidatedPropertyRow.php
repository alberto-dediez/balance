<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;

final readonly class NonConsolidatedPropertyRow
{
    public function __construct(
        public ?Property $property,
        public PropertyId $propertyId,
        public Source $source,
        public ?PropertyCarbonFootprintId $originId,
        public int $year,
        public Emissions $calculatedEmissions,
        public ?string $manualCo2eq,
        public Emissions $emissions,
    ) {}
}
