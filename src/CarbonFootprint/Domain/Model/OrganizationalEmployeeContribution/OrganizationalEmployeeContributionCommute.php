<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

class OrganizationalEmployeeContributionCommute
{
    public function __construct(
        private OrganizationalEmployeeContributionCommuteId $id,
        private OrganizationalEmployeeContribution $contribution,
        private MobilityId $mobilityId,
        private float $distanceKm,
        private int $daysPerWeek,
        private int $vehicleOccupants,
    ) {}

    public function id(): OrganizationalEmployeeContributionCommuteId
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

    public function daysPerWeek(): int
    {
        return $this->daysPerWeek;
    }

    public function vehicleOccupants(): int
    {
        return $this->vehicleOccupants;
    }
}
