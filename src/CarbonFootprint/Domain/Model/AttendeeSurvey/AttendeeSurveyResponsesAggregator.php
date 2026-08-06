<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;

final class AttendeeSurveyResponsesAggregator
{
    public function __construct(private readonly TripLegsAccumulator $legsAccumulator) {}

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
                $this->legsAccumulator->accumulate(
                    $kmByMobilityId,
                    $response->legsOfKind(LegKind::Intercity),
                    $response->intercityTrips(),
                    $response->intercityRoundTrip(),
                );
            }

            if ($response->movedWithinCity()) {
                $this->legsAccumulator->accumulate(
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
}
