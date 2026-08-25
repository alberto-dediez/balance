<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;

final class TripCalculationsAggregator
{
    public function __construct(private readonly TripLegsAccumulator $legsAccumulator) {}

    /**
     * @param list<TripCalculation>      $tripCalculations
     * @param array<string, FactorBasis> $factorBasisByMobilityId
     */
    public function aggregate(array $tripCalculations, array $factorBasisByMobilityId = []): TripCalculationsAggregation
    {
        $count = count($tripCalculations);

        if (0 === $count) {
            return new TripCalculationsAggregation(
                kmByMobilityId: [],
                overnightStays: 0.0,
                calculations: 0,
                totalEmissions: new Emissions(),
                averageEmissions: new Emissions(),
            );
        }

        $kmByMobilityId = [];
        $overnightStays = 0.0;
        $totalEmissions = new Emissions();

        foreach ($tripCalculations as $tripCalculation) {
            $this->legsAccumulator->accumulate(
                $kmByMobilityId,
                $tripCalculation->legs(),
                $tripCalculation->trips(),
                $tripCalculation->roundTrip(),
                $factorBasisByMobilityId,
            );

            $overnightStays += $tripCalculation->overnightStays();
            $totalEmissions = $totalEmissions->add($tripCalculation->emissions());
        }

        return new TripCalculationsAggregation(
            kmByMobilityId: $kmByMobilityId,
            overnightStays: $overnightStays,
            calculations: $count,
            totalEmissions: $totalEmissions,
            averageEmissions: $totalEmissions->dividedBy($count),
        );
    }
}
