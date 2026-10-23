<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Geo;

use App\Shared\Domain\Model\Geo\GeoPlace;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Domain\Model\Geo\PlaceSearchArea;
use App\Shared\Domain\Model\Locale\Locale;

final class NullPlaceFinder implements PlaceFinder
{
    public function isOperative(): bool
    {
        return false;
    }

    public function suggest(string $query, PlaceKind $kind, Locale $locale, ?PlaceSearchArea $area = null): array
    {
        return [];
    }

    public function resolve(string $reference, Locale $locale): ?GeoPlace
    {
        return null;
    }
}
