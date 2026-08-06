<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;

class AttendeeSurveyResponseMother
{
    public static function fromAttendeeSurveyId(string $attendeeSurveyId): AttendeeSurveyResponse
    {
        return self::intercityOnly($attendeeSurveyId, (string) MobilityId::generate(), 300.0, 2, 1);
    }

    public static function intercityOnly(
        string $attendeeSurveyId,
        string $mobilityId,
        float $distanceKm,
        int $trips,
        int $occupants,
        bool $usedHotel = false,
        ?int $hotelNights = null,
        bool $roundTrip = false,
    ): AttendeeSurveyResponse {
        return self::from(
            attendeeSurveyId: $attendeeSurveyId,
            cameFromAnotherCity: true,
            intercityTrips: $trips,
            intercityRoundTrip: $roundTrip,
            usedHotel: $usedHotel,
            hotelNights: $hotelNights,
            legs: [self::leg(1, LegKind::Intercity, $mobilityId, $distanceKm, $occupants)],
        );
    }

    public static function cityOnly(
        string $attendeeSurveyId,
        string $mobilityId,
        float $distanceKm,
        int $trips,
        int $occupants,
        bool $roundTrip = false,
    ): AttendeeSurveyResponse {
        return self::from(
            attendeeSurveyId: $attendeeSurveyId,
            movedWithinCity: true,
            cityTrips: $trips,
            cityRoundTrip: $roundTrip,
            legs: [self::leg(1, LegKind::City, $mobilityId, $distanceKm, $occupants)],
        );
    }

    /** @param list<array{mobilityId: string, distanceKm: float, occupants: int}> $legs */
    public static function multimodalIntercity(
        string $attendeeSurveyId,
        array $legs,
        int $trips = 1,
        bool $roundTrip = false,
    ): AttendeeSurveyResponse {
        $resolvedLegs = [];

        foreach ($legs as $index => $leg) {
            $resolvedLegs[] = self::leg($index + 1, LegKind::Intercity, $leg['mobilityId'], $leg['distanceKm'], $leg['occupants']);
        }

        return self::from(
            attendeeSurveyId: $attendeeSurveyId,
            cameFromAnotherCity: true,
            intercityTrips: $trips,
            intercityRoundTrip: $roundTrip,
            legs: $resolvedLegs,
        );
    }

    /** @param list<ResolvedLeg> $legs */
    public static function from(
        string $attendeeSurveyId,
        bool $cameFromAnotherCity = false,
        bool $intercityRoundTrip = false,
        ?int $intercityTrips = null,
        bool $usedHotel = false,
        ?int $hotelNights = null,
        bool $movedWithinCity = false,
        bool $cityRoundTrip = false,
        ?int $cityTrips = null,
        array $legs = [],
        ?string $id = null,
    ): AttendeeSurveyResponse {
        $response = new AttendeeSurveyResponse(id: new AttendeeSurveyResponseId($id ?? (string) AttendeeSurveyResponseId::generate()));
        $response->register(
            attendeeSurveyId: new AttendeeSurveyId($attendeeSurveyId),
            cameFromAnotherCity: $cameFromAnotherCity,
            origin: null,
            usedHotel: $usedHotel,
            accommodation: null,
            hotelNights: $hotelNights,
            intercityRoundTrip: $intercityRoundTrip,
            intercityTrips: $intercityTrips,
            movedWithinCity: $movedWithinCity,
            cityOrigin: null,
            cityRoundTrip: $cityRoundTrip,
            cityTrips: $cityTrips,
            legs: $legs,
        );

        return $response;
    }

    public static function random(): AttendeeSurveyResponse
    {
        return self::fromAttendeeSurveyId((string) AttendeeSurveyId::generate());
    }

    public static function leg(
        int $position,
        LegKind $kind,
        string $mobilityId,
        float $distanceKm,
        int $occupants = 1,
        DistanceSource $distanceSource = DistanceSource::Manual,
    ): ResolvedLeg {
        return new ResolvedLeg(
            position: $position,
            kind: $kind,
            mobilityId: new MobilityId($mobilityId),
            vehicleOccupants: $occupants,
            origin: new TripWaypoint(),
            destination: new TripWaypoint(),
            distanceKm: $distanceKm,
            distanceSource: $distanceSource,
        );
    }
}
