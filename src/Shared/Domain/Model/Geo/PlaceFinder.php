<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Geo;

use App\Shared\Domain\Model\Locale\Locale;

interface PlaceFinder
{
    public function isOperative(): bool;

    /** @return list<PlaceSuggestion> */
    public function suggest(string $query, PlaceKind $kind, Locale $locale): array;

    public function resolve(string $reference, Locale $locale): ?GeoPlace;
}
