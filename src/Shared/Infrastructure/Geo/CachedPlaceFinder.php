<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Geo;

use App\Shared\Domain\Model\Geo\GeoPlace;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Domain\Model\Locale\Locale;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class CachedPlaceFinder implements PlaceFinder
{
    private const int TTL_SECONDS = 2592000;

    public function __construct(
        private readonly PlaceFinder $placeFinder,
        private readonly CacheInterface $cache,
    ) {}

    public function isOperative(): bool
    {
        return $this->placeFinder->isOperative();
    }

    public function suggest(string $query, PlaceKind $kind, Locale $locale): array
    {
        return $this->placeFinder->suggest($query, $kind, $locale);
    }

    public function resolve(string $reference, Locale $locale): ?GeoPlace
    {
        /** @var GeoPlace|null $place */
        $place = $this->cache->get(
            sprintf('cf_geo_place_%s_%s', $locale->value, hash('xxh128', $reference)),
            function (CacheItemInterface $item) use ($reference, $locale): ?GeoPlace {
                $item->expiresAfter(self::TTL_SECONDS);

                return $this->placeFinder->resolve($reference, $locale);
            },
        );

        return $place;
    }
}
