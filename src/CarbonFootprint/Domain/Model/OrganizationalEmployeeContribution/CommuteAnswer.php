<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

final class CommuteAnswer
{
    public function __construct(
        public readonly MobilityId $mobilityId,
        public readonly float $distanceKm,
        public readonly int $daysPerWeek,
        public readonly int $vehicleOccupants,
    ) {}
}
