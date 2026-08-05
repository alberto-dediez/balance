<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Geo;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\RouteDistance;
use App\CarbonFootprint\Domain\Model\Geo\RouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\Shared\Domain\Model\Geo\GeoPoint;

class FixedRouteDistanceCalculator implements RouteDistanceCalculator
{
    /** @var array<string, float> */
    private array $distances = [];

    private int $calls = 0;

    public function __construct(private readonly bool $alwaysFails = false) {}

    public function set(GeoPoint $origin, GeoPoint $destination, TravelMode $mode, float $kilometers): void
    {
        $this->distances[$this->key($origin, $destination, $mode)] = $kilometers;
    }

    public function calculate(GeoPoint $origin, GeoPoint $destination, TravelMode $mode): ?RouteDistance
    {
        ++$this->calls;

        if ($this->alwaysFails) {
            return null;
        }

        $kilometers = $this->distances[$this->key($origin, $destination, $mode)] ?? null;

        if (null === $kilometers) {
            return null;
        }

        return new RouteDistance($kilometers, null, DistanceSource::Route);
    }

    public function calls(): int
    {
        return $this->calls;
    }

    private function key(GeoPoint $origin, GeoPoint $destination, TravelMode $mode): string
    {
        return sprintf('%s|%s,%s|%s,%s', $mode->value, $origin->latitude, $origin->longitude, $destination->latitude, $destination->longitude);
    }
}
