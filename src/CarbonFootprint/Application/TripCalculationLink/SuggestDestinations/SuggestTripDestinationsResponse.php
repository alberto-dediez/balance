<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\SuggestDestinations;

use App\Shared\Domain\Model\Geo\PlaceSuggestion;

final class SuggestTripDestinationsResponse
{
    /** @param list<PlaceSuggestion> $suggestions */
    public function __construct(
        public readonly array $suggestions,
    ) {}
}
