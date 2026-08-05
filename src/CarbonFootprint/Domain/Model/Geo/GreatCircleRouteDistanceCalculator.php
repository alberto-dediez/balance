<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Geo;

use App\Shared\Domain\Model\Geo\GeoPoint;

final class GreatCircleRouteDistanceCalculator implements RouteDistanceCalculator
{
    public function calculate(GeoPoint $origin, GeoPoint $destination, TravelMode $mode): RouteDistance
    {
        return new RouteDistance(
            kilometers: $origin->greatCircleDistanceKm($destination) * $mode->detourFactor(),
            durationSeconds: null,
            source: DistanceSource::GreatCircle,
        );
    }
}
