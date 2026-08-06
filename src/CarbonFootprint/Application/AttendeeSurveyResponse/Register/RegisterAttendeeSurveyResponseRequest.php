<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurveyResponse\Register;

use App\CarbonFootprint\Application\Trip\LegInput;

final class RegisterAttendeeSurveyResponseRequest
{
    /**
     * @param list<LegInput> $intercityLegs
     * @param list<LegInput> $cityLegs
     */
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly bool $cameFromAnotherCity,
        public readonly ?string $originPlaceReference,
        public readonly bool $usedHotel,
        public readonly ?string $accommodationPlaceReference,
        public readonly ?int $hotelNights,
        public readonly bool $intercityRoundTrip,
        public readonly ?int $intercityTrips,
        public readonly array $intercityLegs,
        public readonly bool $movedWithinCity,
        public readonly ?string $cityOriginPlaceReference,
        public readonly bool $cityRoundTrip,
        public readonly ?int $cityTrips,
        public readonly array $cityLegs,
    ) {}
}
