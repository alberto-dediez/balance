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
