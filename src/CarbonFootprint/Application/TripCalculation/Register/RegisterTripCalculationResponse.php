<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\Register;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;

final class RegisterTripCalculationResponse
{
    public function __construct(
        public readonly TripCalculationId $tripCalculationId,
        public readonly Emissions $emissions,
        public readonly Emissions $mobilityEmissions,
        public readonly Emissions $overnightEmissions,
        public readonly bool $overnightStayCatalogMissing = false,
    ) {}
}
