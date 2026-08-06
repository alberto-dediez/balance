<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

final readonly class LegInput
{
    public function __construct(
        public string $mobilityId,
        public ?int $vehicleOccupants = null,
        public ?string $destinationPlaceReference = null,
        public ?float $manualDistanceKm = null,
    ) {}
}
