<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\Register;

use App\CarbonFootprint\Application\Trip\LegInput;

final class RegisterTripCalculationRequest
{
    /** @param list<LegInput> $legs */
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly ?string $originPlaceReference,
        public readonly bool $roundTrip,
        public readonly ?int $trips,
        public readonly ?int $nights,
        public readonly array $legs,
        public readonly string $consentIp = '',
        public readonly string $consentUserAgent = '',
        public readonly string $consentAcceptLanguage = '',
    ) {}
}
