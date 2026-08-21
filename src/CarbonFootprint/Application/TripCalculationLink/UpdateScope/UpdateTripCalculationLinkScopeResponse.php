<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\UpdateScope;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateTripCalculationLinkScopeResponse implements CommandResponse
{
    public function __construct(
        public TripCalculationLink $tripCalculationLink,
    ) {}
}
