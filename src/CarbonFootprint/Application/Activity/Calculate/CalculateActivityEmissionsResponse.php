<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Calculate;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\Shared\Application\CommandBus\CommandResponse;

class CalculateActivityEmissionsResponse implements CommandResponse
{
    public function __construct(
        public EmissionFactor $emissionFactor,
        public Emissions $emissions,
    ) {}
}
