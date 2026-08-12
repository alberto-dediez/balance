<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Trip;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

interface TripLeg
{
    public function mobilityId(): MobilityId;

    public function distanceKm(): ?float;

    public function vehicleOccupants(): ?int;
}
