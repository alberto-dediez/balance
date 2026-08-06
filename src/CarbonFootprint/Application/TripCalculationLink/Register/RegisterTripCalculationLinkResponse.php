<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\Register;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterTripCalculationLinkResponse implements CommandResponse
{
    public function __construct(
        public TripCalculationLink $tripCalculationLink,
    ) {}
}
