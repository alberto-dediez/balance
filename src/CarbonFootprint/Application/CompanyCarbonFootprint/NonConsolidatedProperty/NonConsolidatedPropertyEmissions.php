<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Property\Domain\Model\Property\Property;

final readonly class NonConsolidatedPropertyEmissions
{
    public function __construct(
        public Property $property,
        public Source $source,
        public ?PropertyCarbonFootprint $origin,
        public Emissions $calculatedEmissions,
    ) {}
}
