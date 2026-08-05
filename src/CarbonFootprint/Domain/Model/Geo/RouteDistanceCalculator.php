<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Geo;

use App\Shared\Domain\Model\Geo\GeoPoint;

interface RouteDistanceCalculator
{
    /** Returns the one way distance, or null when no route could be computed. Never throws. */
    public function calculate(GeoPoint $origin, GeoPoint $destination, TravelMode $mode): ?RouteDistance;
}
