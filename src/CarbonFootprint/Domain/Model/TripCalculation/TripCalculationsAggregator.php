<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;

final class TripCalculationsAggregator
{
    public function __construct(private readonly TripLegsAccumulator $legsAccumulator) {}

    /** @param list<TripCalculation> $tripCalculations */
    public function aggregate(array $tripCalculations): TripCalculationsAggregation
    {
        $count = count($tripCalculations);

        if (0 === $count) {
            return new TripCalculationsAggregation(
                kmByMobilityId: [],
                nights: 0.0,
                calculations: 0,
                totalEmissions: new Emissions(),
                averageEmissions: new Emissions(),
            );
        }

        $kmByMobilityId = [];
        $nights = 0.0;
        $totalEmissions = new Emissions();

        foreach ($tripCalculations as $tripCalculation) {
            $this->legsAccumulator->accumulate(
                $kmByMobilityId,
                $tripCalculation->legs(),
                $tripCalculation->trips(),
                $tripCalculation->roundTrip(),
            );

            $nights += $tripCalculation->nights() ?? 0;
            $totalEmissions = $totalEmissions->add($tripCalculation->emissions());
        }

        return new TripCalculationsAggregation(
            kmByMobilityId: $kmByMobilityId,
            nights: $nights,
            calculations: $count,
            totalEmissions: $totalEmissions,
            averageEmissions: $totalEmissions->dividedBy($count),
        );
    }
}
