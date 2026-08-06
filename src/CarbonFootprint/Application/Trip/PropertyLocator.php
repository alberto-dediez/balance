<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\Property\Domain\Model\Property\Property;
use App\Shared\Domain\Model\Geo\GeoPoint;

/** Resolves the geographic point of a property from the coordinates stored in its info. */
final class PropertyLocator
{
    public function locate(?Property $property): ?GeoPoint
    {
        if (null === $property) {
            return null;
        }

        $latitude = $this->coordinate($property->info()->latitude());
        $longitude = $this->coordinate($property->info()->longitude());

        if (null === $latitude || null === $longitude) {
            return null;
        }

        return new GeoPoint($latitude, $longitude);
    }

    private function coordinate(?string $value): ?float
    {
        if (null === $value || !is_numeric(trim($value))) {
            return null;
        }

        return (float) trim($value);
    }
}
