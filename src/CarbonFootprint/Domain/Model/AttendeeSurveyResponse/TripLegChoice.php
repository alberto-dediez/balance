<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

final readonly class TripLegChoice
{
    public function __construct(
        public MobilityId $mobilityId,
        public ?TravelMode $travelMode = null,
        public ?int $vehicleOccupants = null,
        public ?float $manualDistanceKm = null,
    ) {}
}
