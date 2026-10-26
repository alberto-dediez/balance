<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\Import;

use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportTripCalculationResponse implements CommandResponse
{
    public function __construct(
        public TripCalculationId $tripCalculationId,
    ) {}
}
