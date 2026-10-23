<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Geo;

final readonly class GeoBounds
{
    public function __construct(
        public GeoPoint $low,
        public GeoPoint $high,
    ) {}

    public function contains(GeoPoint $point): bool
    {
        if ($point->latitude < $this->low->latitude || $point->latitude > $this->high->latitude) {
            return false;
        }

        if ($this->low->longitude <= $this->high->longitude) {
            return $point->longitude >= $this->low->longitude && $point->longitude <= $this->high->longitude;
        }

        return $point->longitude >= $this->low->longitude || $point->longitude <= $this->high->longitude;
    }
}
