<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use App\Shared\Domain\Model\Geo\GeoPlace;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Domain\Model\Geo\PlaceSuggestion;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class AcceptancePlaceFinder implements PlaceFinder
{
    /** @var array<string, GeoPlace> */
    private array $places = [];

    private bool $operative = true;

    public function add(string $reference, string $label, float $latitude, float $longitude): void
    {
        $this->places[$reference] = new GeoPlace($reference, $label, new GeoPoint($latitude, $longitude));
    }

    public function disable(): void
    {
        $this->operative = false;
    }

    public function isOperative(): bool
    {
        return $this->operative;
    }

    public function suggest(string $query, PlaceKind $kind, Locale $locale): array
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
