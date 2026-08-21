<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\UpdateScope;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\Shared\Application\CommandBus\Command;

class UpdateTripCalculationLinkScopeRequest implements Command
{
    public function __construct(
        public TripCalculationLinkId $tripCalculationLinkId,
        public TripCalculationScope $scope,
    ) {}
}
