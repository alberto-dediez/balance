<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;

final class AttendeeSurveyResponsesAggregator
{
    public function __construct(private readonly TripLegsAccumulator $legsAccumulator) {}

    /**
     * @param list<AttendeeSurveyResponse> $responses
     * @param array<string, FactorBasis>   $factorBasisByMobilityId
     */
    public function aggregate(array $responses, int $realAttendees, array $factorBasisByMobilityId = []): AttendeeSurveyAggregation
    {
        if ([] === $responses) {
            return new AttendeeSurveyAggregation(kmByMobilityId: [], overnightStays: 0.0);
        }

        $kmByMobilityId = [];
        $overnightStays = 0.0;

        foreach ($responses as $response) {
            if ($response->cameFromAnotherCity()) {
                $this->legsAccumulator->accumulatePerPerson(
                    $kmByMobilityId,
                    $response->legsOfKind(LegKind::Intercity),
                    $response->intercityTrips(),
                    $response->intercityRoundTrip(),
                    $factorBasisByMobilityId,
                );
            }

            if ($response->movedWithinCity()) {
                $this->legsAccumulator->accumulatePerPerson(
                    $kmByMobilityId,
                    $response->legsOfKind(LegKind::City),
                    $response->cityTrips(),
                    $response->cityRoundTrip(),
                    $factorBasisByMobilityId,
                );
            }

            if ($response->usedHotel()) {
                $overnightStays += $response->overnightStaysPerPerson();
            }
        }

        $extrapolationFactor = $realAttendees / count($responses);

        $kmByMobilityId = array_map(
            static fn(float $km): float => $km * $extrapolationFactor,
            $kmByMobilityId,
        );

        return new AttendeeSurveyAggregation(
            kmByMobilityId: $kmByMobilityId,
            overnightStays: $overnightStays * $extrapolationFactor,
        );
    }
}
