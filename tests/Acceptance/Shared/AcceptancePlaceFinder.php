<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPlace;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Domain\Model\Geo\PlaceSearchArea;
use App\Shared\Domain\Model\Geo\PlaceSuggestion;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class AcceptancePlaceFinder implements PlaceFinder
{
    /** @var array<string, GeoPlace> */
    private array $places = [];

    private bool $operative = true;

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

    public function disable(): void
    {
        $this->operative = false;
    }

    public function isOperative(): bool
    {
        return $this->operative;
    }

    public function suggest(string $query, PlaceKind $kind, Locale $locale, ?PlaceSearchArea $area = null): array
    {
        if (!$this->operative) {
            return [];
        }

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
        if (!$this->operative) {
            return null;
        }

        return $this->places[$reference] ?? null;
    }
}
