<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\SuggestDestinations;

final class SuggestTripDestinationsRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly string $query,
    ) {}
}
