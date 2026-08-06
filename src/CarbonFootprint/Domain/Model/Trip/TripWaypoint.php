<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Trip;

use App\Shared\Domain\Model\Geo\GeoPoint;

final readonly class TripWaypoint
{
    public function __construct(
        public ?string $reference = null,
        public ?string $label = null,
        public ?GeoPoint $point = null,
    ) {}
}
