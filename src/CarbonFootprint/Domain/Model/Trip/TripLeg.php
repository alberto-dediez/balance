<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Trip;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

/** Minimum contract a persisted leg must fulfil to be accumulated into kilometres. */
interface TripLeg
{
    public function mobilityId(): MobilityId;

    /** One way distance of this leg. */
    public function distanceKm(): ?float;

    public function vehicleOccupants(): ?int;
}
