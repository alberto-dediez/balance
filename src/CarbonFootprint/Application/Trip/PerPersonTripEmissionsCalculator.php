<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;

final class PerPersonTripEmissionsCalculator
{
    public function __construct(
        private TripLegsAccumulator $legsAccumulator,
        private MobilityFactorBasisProvider $mobilityFactorBasisProvider,
        private TripEmissionsCalculator $tripEmissionsCalculator,
    ) {}

    /** @param list<AttendeeSurveyResponse> $responses */
    public function forAttendeeSurveyResponses(array $responses): Emissions
    {
        if ([] === $responses) {
            return new Emissions();
        }

        $factorBasisByMobilityId = $this->mobilityFactorBasisProvider->factorBasisByMobilityId();
        $kmByMobilityId = [];
        $overnightStays = 0.0;
        $emissionFactorGroupId = null;
        $calculationYear = null;

        foreach ($responses as $response) {
            $this->legsAccumulator->accumulatePerPerson(
                $kmByMobilityId,
                $response->legsOfKind(LegKind::Intercity),
                $response->intercityTrips(),
                $response->intercityRoundTrip(),
                $factorBasisByMobilityId,
            );

            $this->legsAccumulator->accumulatePerPerson(
                $kmByMobilityId,
                $response->legsOfKind(LegKind::City),
                $response->cityTrips(),
                $response->cityRoundTrip(),
                $factorBasisByMobilityId,
            );

            $overnightStays += $response->overnightStaysPerPerson();
            $emissionFactorGroupId ??= $response->emissionFactorGroupId();
            $calculationYear ??= $response->calculationYear();
        }

        return $this->average($kmByMobilityId, $overnightStays, $emissionFactorGroupId, $calculationYear, count($responses));
    }

    /** @param list<TripCalculation> $tripCalculations */
    public function forTripCalculations(array $tripCalculations): Emissions
    {
        if ([] === $tripCalculations) {
            return new Emissions();
        }

        $factorBasisByMobilityId = $this->mobilityFactorBasisProvider->factorBasisByMobilityId();
        $kmByMobilityId = [];
        $overnightStays = 0.0;
        $emissionFactorGroupId = null;
        $calculationYear = null;

        foreach ($tripCalculations as $tripCalculation) {
            $this->legsAccumulator->accumulatePerPerson(
                $kmByMobilityId,
                $tripCalculation->legs(),
                $tripCalculation->trips(),
                $tripCalculation->roundTrip(),
                $factorBasisByMobilityId,
            );

            $overnightStays += $tripCalculation->overnightStaysPerPerson();
            $emissionFactorGroupId ??= $tripCalculation->emissionFactorGroupId();
            $calculationYear ??= $tripCalculation->calculationYear();
        }

        return $this->average($kmByMobilityId, $overnightStays, $emissionFactorGroupId, $calculationYear, count($tripCalculations));
    }

    /** @param array<string, float> $kmByMobilityId */
    private function average(
        array $kmByMobilityId,
        float $overnightStays,
        ?EmissionFactorGroupId $emissionFactorGroupId,
        ?int $calculationYear,
        int $people,
    ): Emissions {
        if (null === $emissionFactorGroupId || null === $calculationYear || $people < 1) {
            return new Emissions();
        }

        try {
            $tripEmissions = $this->tripEmissionsCalculator->calculate(
                kmByMobilityId: $kmByMobilityId,
                nights: $overnightStays,
                emissionFactorGroupId: $emissionFactorGroupId,
                year: $calculationYear,
            );
        } catch (\InvalidArgumentException) {
            return new Emissions();
        }

        return $tripEmissions->total()->dividedBy($people);
    }
}
