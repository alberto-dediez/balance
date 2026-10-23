<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetResults;

use App\CarbonFootprint\Application\Contribution\ContributionsSummary;
use App\CarbonFootprint\Application\Trip\MobilityBreakdownRow;
use App\CarbonFootprint\Application\Trip\OriginDestinationFlow;
use App\CarbonFootprint\Application\Trip\OriginFlow;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregation;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\Property\Domain\Model\Property\Property;
use App\Shared\Domain\Model\Geo\GeoPoint;

final class GetTripCalculationResultsResponse
{
    /**
     * @param list<MobilityBreakdownRow>  $mobilityBreakdown
     * @param array<string, int>          $calculationsByDay
     * @param array<string, int>          $topOrigins
     * @param list<OriginFlow>            $originFlows
     * @param array<string, int>          $topDestinations
     * @param list<OriginDestinationFlow> $originDestinationFlows
     */
    public function __construct(
        public readonly ?Property $property,
        public readonly string $ownerName,
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
        public readonly array $originFlows = [],
        public readonly ?GeoPoint $destination = null,
        public readonly int $unlocatedCalculations = 0,
        public readonly array $topDestinations = [],
        public readonly array $originDestinationFlows = [],
        public readonly int $undestinedCalculations = 0,
    ) {}
}
