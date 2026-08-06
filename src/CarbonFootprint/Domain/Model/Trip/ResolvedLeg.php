<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Trip;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

final readonly class ResolvedLeg implements TripLeg
{
    public function __construct(
        public int $position,
        public LegKind $kind,
        public MobilityId $mobilityId,
        public ?int $vehicleOccupants,
        public TripWaypoint $origin,
        public TripWaypoint $destination,
        public ?float $distanceKm,
        public DistanceSource $distanceSource,
    ) {}

    public function mobilityId(): MobilityId
    {
        return $this->mobilityId;
    }

    public function distanceKm(): ?float
    {
        return $this->distanceKm;
    }

    public function vehicleOccupants(): ?int
    {
        return $this->vehicleOccupants;
    }
}
