<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetResults;

use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregation;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\Property\Domain\Model\Property\Property;

final class GetTripCalculationResultsResponse
{
    /** @param list<MobilityBreakdownRow> $mobilityBreakdown */
    public function __construct(
        public readonly Property $property,
        public readonly ?TripCalculationLink $tripCalculationLink,
        public readonly TripCalculationsAggregation $aggregation,
        public readonly array $mobilityBreakdown,
    ) {}
}
