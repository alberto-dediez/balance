<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

class OrganizationalEmployeeContributionTrip
{
    public function __construct(
        private OrganizationalEmployeeContributionTripId $id,
        private OrganizationalEmployeeContribution $contribution,
        private MobilityId $mobilityId,
        private float $distanceKm,
        private int $trips,
        private int $vehicleOccupants,
        private int $hotelNights,
    ) {}

    public function id(): OrganizationalEmployeeContributionTripId
    {
        return $this->id;
    }

    public function contribution(): OrganizationalEmployeeContribution
    {
        return $this->contribution;
    }

    public function mobilityId(): MobilityId
    {
        return $this->mobilityId;
    }

    public function distanceKm(): float
    {
        return $this->distanceKm;
    }

    public function trips(): int
    {
        return $this->trips;
    }

    public function vehicleOccupants(): int
    {
        return $this->vehicleOccupants;
    }

    public function hotelNights(): int
    {
        return $this->hotelNights;
    }
}
