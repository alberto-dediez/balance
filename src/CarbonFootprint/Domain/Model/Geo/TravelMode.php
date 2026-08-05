<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Geo;

enum TravelMode: string
{
    case Driving = 'driving';
    case Transit = 'transit';
    case Cycling = 'cycling';
    case Walking = 'walking';
    case Flight = 'flight';

    /**
     * Multiplier applied to the great circle distance when no real route is available,
     * so the fallback approximates the detour of an actual trip.
     */
    public function detourFactor(): float
    {
        return match ($this) {
            self::Driving => 1.30,
            self::Transit => 1.35,
            self::Cycling, self::Walking => 1.20,
            self::Flight => 1.10,
        };
    }
}
