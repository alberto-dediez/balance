<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetResults;

use App\CarbonFootprint\Application\Contribution\ContributionsSummary;
use App\CarbonFootprint\Application\Trip\MobilityBreakdownRow;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregation;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\Property\Domain\Model\Property\Property;

final class GetTripCalculationResultsResponse
{
    /**
     * @param list<MobilityBreakdownRow> $mobilityBreakdown
     * @param array<string, int>         $calculationsByDay
     * @param array<string, int>         $topOrigins
     */
    public function __construct(
        public readonly Property $property,
        public readonly ?TripCalculationLink $tripCalculationLink,
        public readonly TripCalculationsAggregation $aggregation,
        public readonly array $mobilityBreakdown,
        public readonly ContributionsSummary $contributions,
        public readonly Emissions $mobilityEmissions,
        public readonly Emissions $overnightEmissions,
        public readonly Emissions $emissionsPerPerson,
        public readonly array $calculationsByDay,
        public readonly array $topOrigins,
        public readonly int $withOvernightStayCount,
        public readonly int $roundTripCount,
    ) {}
}
