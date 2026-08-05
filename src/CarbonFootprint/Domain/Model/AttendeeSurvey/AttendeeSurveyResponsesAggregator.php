<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseLeg;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\LegKind;

final class AttendeeSurveyResponsesAggregator
{
    /** @param list<AttendeeSurveyResponse> $responses */
    public function aggregate(array $responses, int $realAttendees): AttendeeSurveyAggregation
    {
        $count = count($responses);

        if (0 === $count) {
            return new AttendeeSurveyAggregation(kmByMobilityId: [], hotelNights: 0.0);
        }

        $kmByMobilityId = [];
        $hotelNights = 0.0;

        foreach ($responses as $response) {
            if ($response->cameFromAnotherCity()) {
                $this->accumulate(
                    $kmByMobilityId,
                    $response->legsOfKind(LegKind::Intercity),
                    $response->intercityTrips(),
                    $response->intercityRoundTrip(),
                );
            }

            if ($response->movedWithinCity()) {
                $this->accumulate(
                    $kmByMobilityId,
                    $response->legsOfKind(LegKind::City),
                    $response->cityTrips(),
                    $response->cityRoundTrip(),
                );
            }

            if ($response->usedHotel() && null !== $response->hotelNights()) {
                $hotelNights += $response->hotelNights();
            }
        }

        $extrapolationFactor = $realAttendees / $count;

        $kmByMobilityId = array_map(
            static fn(float $km): float => $km * $extrapolationFactor,
            $kmByMobilityId,
        );

        return new AttendeeSurveyAggregation(
            kmByMobilityId: $kmByMobilityId,
            hotelNights: $hotelNights * $extrapolationFactor,
        );
    }

    /**
     * @param array<string, float>            $kmByMobilityId
     * @param list<AttendeeSurveyResponseLeg> $legs
     */
    private function accumulate(array &$kmByMobilityId, array $legs, ?int $trips, bool $roundTrip): void
    {
        if (null === $trips || $trips < 1) {
            return;
        }

        $roundTripFactor = $roundTrip ? 2 : 1;

        foreach ($legs as $leg) {
            $distanceKm = $leg->distanceKm();

            if (null === $distanceKm) {
                continue;
            }

            $mobilityId = (string) $leg->mobilityId();
            $occupants = max(1, $leg->vehicleOccupants() ?? 1);
            $km = $distanceKm * $trips * $roundTripFactor / $occupants;

            $kmByMobilityId[$mobilityId] = ($kmByMobilityId[$mobilityId] ?? 0.0) + $km;
        }
    }
}
