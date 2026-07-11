<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

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
        int $days,
        int $occupants,
        bool $usedHotel = false,
        ?int $hotelNights = null,
    ): AttendeeSurveyResponse {
        return self::from(
            attendeeSurveyId: $attendeeSurveyId,
            cameFromAnotherCity: true,
            intercityDays: $days,
            intercityMobilityId: $mobilityId,
            intercityVehicleOccupants: $occupants,
            intercityDistanceKm: $distanceKm,
            usedHotel: $usedHotel,
            hotelNights: $hotelNights,
        );
    }

    public static function cityOnly(
        string $attendeeSurveyId,
        string $mobilityId,
        float $distanceKm,
        int $days,
        int $occupants,
    ): AttendeeSurveyResponse {
        return self::from(
            attendeeSurveyId: $attendeeSurveyId,
            cameFromAnotherCity: false,
            cityDays: $days,
            cityMobilityId: $mobilityId,
            cityVehicleOccupants: $occupants,
            cityDistanceKm: $distanceKm,
        );
    }

    public static function from(
        string $attendeeSurveyId,
        bool $cameFromAnotherCity = false,
        ?int $intercityDays = null,
        ?string $intercityMobilityId = null,
        ?int $intercityVehicleOccupants = null,
        ?float $intercityDistanceKm = null,
        bool $usedHotel = false,
        ?int $hotelNights = null,
        ?int $cityDays = null,
        ?string $cityMobilityId = null,
        ?int $cityVehicleOccupants = null,
        ?float $cityDistanceKm = null,
        ?string $id = null,
    ): AttendeeSurveyResponse {
        $response = new AttendeeSurveyResponse(id: new AttendeeSurveyResponseId($id ?? (string) AttendeeSurveyResponseId::generate()));
        $response->register(
            attendeeSurveyId: new AttendeeSurveyId($attendeeSurveyId),
            cameFromAnotherCity: $cameFromAnotherCity,
            intercityDays: $intercityDays,
            intercityMobilityId: null !== $intercityMobilityId ? new MobilityId($intercityMobilityId) : null,
            intercityVehicleOccupants: $intercityVehicleOccupants,
            intercityDistanceKm: $intercityDistanceKm,
            usedHotel: $usedHotel,
            hotelNights: $hotelNights,
            cityDays: $cityDays,
            cityMobilityId: null !== $cityMobilityId ? new MobilityId($cityMobilityId) : null,
            cityVehicleOccupants: $cityVehicleOccupants,
            cityDistanceKm: $cityDistanceKm,
        );

        return $response;
    }

    public static function random(): AttendeeSurveyResponse
    {
        return self::fromAttendeeSurveyId((string) AttendeeSurveyId::generate());
    }
}
