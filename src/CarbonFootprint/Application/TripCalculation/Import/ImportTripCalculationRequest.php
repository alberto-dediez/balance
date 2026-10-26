<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\Import;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Shared\Application\CommandBus\Command;

class ImportTripCalculationRequest implements Command
{
    public function __construct(
        public TripCalculationLinkId $tripCalculationLinkId,
        public ?TripWaypoint $origin,
        public ?TripWaypoint $destination,
        public bool $roundTrip,
        public ?int $nights,
        public ?int $people,
        public ?MobilityId $mobilityId,
        public ?int $vehicleOccupants,
        public ?float $distanceKm,
        public Emissions $mobilityEmissions,
        public Emissions $overnightEmissions,
        public \DateTimeImmutable $registeredAt,
    ) {}
}
