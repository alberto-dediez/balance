<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Geo;

use App\Shared\Domain\Model\Geo\GeoPoint;

interface RouteDistanceCalculator
{
    public function calculate(GeoPoint $origin, GeoPoint $destination, TravelMode $mode): ?RouteDistance;
}
