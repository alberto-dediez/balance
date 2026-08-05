<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Geo;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\RouteDistance;
use App\CarbonFootprint\Domain\Model\Geo\RouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\Shared\Domain\Model\Geo\GeoPoint;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class CachedRouteDistanceCalculator implements RouteDistanceCalculator
{
    private const int TTL_SECONDS = 2592000;
    private const int DEGRADED_TTL_SECONDS = 3600;

    public function __construct(
        private readonly RouteDistanceCalculator $calculator,
        private readonly CacheInterface $cache,
    ) {}

    public function calculate(GeoPoint $origin, GeoPoint $destination, TravelMode $mode): ?RouteDistance
    {
        /** @var RouteDistance|null $distance */
        $distance = $this->cache->get(
            $this->cacheKey($origin, $destination, $mode),
            function (CacheItemInterface $item) use ($origin, $destination, $mode): ?RouteDistance {
                $item->expiresAfter(self::TTL_SECONDS);

                $distance = $this->calculator->calculate($origin, $destination, $mode);

                if (null === $distance || DistanceSource::Route !== $distance->source) {
                    $item->expiresAfter(self::DEGRADED_TTL_SECONDS);
                }

                return $distance;
            },
        );

        return $distance;
    }

    private function cacheKey(GeoPoint $origin, GeoPoint $destination, TravelMode $mode): string
    {
        return sprintf(
            'cf_geo_route_%s_%s_%s_%s_%s',
            $mode->value,
            number_format($origin->latitude, 5, '.', ''),
            number_format($origin->longitude, 5, '.', ''),
            number_format($destination->latitude, 5, '.', ''),
            number_format($destination->longitude, 5, '.', ''),
        );
    }
}
