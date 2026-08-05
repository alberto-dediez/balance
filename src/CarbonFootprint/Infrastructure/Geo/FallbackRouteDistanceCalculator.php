<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Geo;

use App\CarbonFootprint\Domain\Model\Geo\GreatCircleRouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\RouteDistance;
use App\CarbonFootprint\Domain\Model\Geo\RouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\Shared\Domain\Model\Geo\GeoPoint;

final class FallbackRouteDistanceCalculator implements RouteDistanceCalculator
{
    public function __construct(
        private readonly RouteDistanceCalculator $primary,
        private readonly GreatCircleRouteDistanceCalculator $fallback,
    ) {}

    public function calculate(GeoPoint $origin, GeoPoint $destination, TravelMode $mode): RouteDistance
    {
        return $this->primary->calculate($origin, $destination, $mode)
            ?? $this->fallback->calculate($origin, $destination, $mode);
    }
}
