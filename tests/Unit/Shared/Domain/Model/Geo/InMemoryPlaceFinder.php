<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Geo;

use App\Shared\Domain\Model\Geo\GeoPlace;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Domain\Model\Geo\PlaceSuggestion;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryPlaceFinder implements PlaceFinder
{
    /** @var array<string, GeoPlace> */
    private array $places = [];

    private int $resolveCalls = 0;

    public function add(string $reference, string $label, float $latitude, float $longitude): void
    {
        $this->places[$reference] = new GeoPlace($reference, $label, new GeoPoint($latitude, $longitude));
    }

    public function isOperative(): bool
    {
        return true;
    }

    public function suggest(string $query, PlaceKind $kind, Locale $locale): array
    {
        $suggestions = [];

        foreach ($this->places as $place) {
            if (false === stripos($place->label, $query)) {
                continue;
            }

            $suggestions[] = new PlaceSuggestion($place->reference, $place->label);
        }

        return $suggestions;
    }

    public function resolve(string $reference, Locale $locale): ?GeoPlace
    {
        ++$this->resolveCalls;

        return $this->places[$reference] ?? null;
    }

    public function resolveCalls(): int
    {
        return $this->resolveCalls;
    }
}
