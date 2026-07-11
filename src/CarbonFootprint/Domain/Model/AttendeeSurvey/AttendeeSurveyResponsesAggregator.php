<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;

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
            if ($response->cameFromAnotherCity()
                && null !== $response->intercityMobilityId()
                && null !== $response->intercityDistanceKm()
                && null !== $response->intercityDays()
            ) {
                $mobilityId = (string) $response->intercityMobilityId();
                $occupants = max(1, $response->intercityVehicleOccupants() ?? 1);
                $km = $response->intercityDistanceKm() * $response->intercityDays() / $occupants;

                $kmByMobilityId[$mobilityId] = ($kmByMobilityId[$mobilityId] ?? 0.0) + $km;
            }

            if (null !== $response->cityMobilityId()
                && null !== $response->cityDistanceKm()
                && null !== $response->cityDays()
            ) {
                $mobilityId = (string) $response->cityMobilityId();
                $occupants = max(1, $response->cityVehicleOccupants() ?? 1);
                $km = $response->cityDistanceKm() * $response->cityDays() / $occupants;

                $kmByMobilityId[$mobilityId] = ($kmByMobilityId[$mobilityId] ?? 0.0) + $km;
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
}
