<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Geo;

use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPlace;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Domain\Model\Geo\PlaceSearchArea;
use App\Shared\Domain\Model\Geo\PlaceSuggestion;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryPlaceFinder implements PlaceFinder
{
    /** @var array<string, GeoPlace> */
    private array $places = [];

    private int $resolveCalls = 0;

    private int $suggestCalls = 0;

    private ?PlaceSearchArea $lastArea = null;

    /** @param list<string> $areaNames */
    public function add(
        string $reference,
        string $label,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?GeoBounds $viewport = null,
        array $areaNames = [],
        ?string $countryCode = null,
    ): void {
        $this->places[$reference] = new GeoPlace(
            $reference,
            $label,
            new GeoPoint($latitude, $longitude),
            $name,
            $viewport,
            $areaNames,
            $countryCode,
        );
    }

    public function isOperative(): bool
    {
        return true;
    }

    public function suggest(string $query, PlaceKind $kind, Locale $locale, ?PlaceSearchArea $area = null): array
    {
        ++$this->suggestCalls;
        $this->lastArea = $area;

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

    public function suggestCalls(): int
    {
        return $this->suggestCalls;
    }

    public function lastArea(): ?PlaceSearchArea
    {
        return $this->lastArea;
    }
}
