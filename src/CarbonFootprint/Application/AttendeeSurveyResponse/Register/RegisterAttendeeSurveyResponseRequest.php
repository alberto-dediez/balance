<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurveyResponse\Register;

final class RegisterAttendeeSurveyResponseRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly bool $cameFromAnotherCity,
        public readonly ?int $intercityDays,
        public readonly ?string $intercityMobilityId,
        public readonly ?int $intercityVehicleOccupants,
        public readonly ?float $intercityDistanceKm,
        public readonly bool $usedHotel,
        public readonly ?int $hotelNights,
        public readonly ?int $cityDays,
        public readonly ?string $cityMobilityId,
        public readonly ?int $cityVehicleOccupants,
        public readonly ?float $cityDistanceKm,
    ) {}
}
