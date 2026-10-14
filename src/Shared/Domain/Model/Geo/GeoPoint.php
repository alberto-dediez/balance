<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Geo;

final readonly class GeoPoint
{
    private const float EARTH_RADIUS_KM = 6371.0;

    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}

    public function greatCircleDistanceKm(self $other): float
    {
        $latitudeFrom = deg2rad($this->latitude);
        $latitudeTo = deg2rad($other->latitude);
        $latitudeDelta = $latitudeTo - $latitudeFrom;
        $longitudeDelta = deg2rad($other->longitude) - deg2rad($this->longitude);

        $angle = sin($latitudeDelta / 2) ** 2
            + cos($latitudeFrom) * cos($latitudeTo) * sin($longitudeDelta / 2) ** 2;

        return 2 * asin(min(1.0, sqrt($angle))) * self::EARTH_RADIUS_KM;
    }

    public function equals(self $other): bool
    {
        return $this->latitude === $other->latitude && $this->longitude === $other->longitude;
    }
}
