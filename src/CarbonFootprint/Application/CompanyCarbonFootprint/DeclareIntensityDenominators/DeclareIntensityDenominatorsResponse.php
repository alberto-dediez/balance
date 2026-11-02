<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\DeclareIntensityDenominators;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\Shared\Application\CommandBus\CommandResponse;

class DeclareIntensityDenominatorsResponse implements CommandResponse
{
    public function __construct(
        public CompanyCarbonFootprint $companyCarbonFootprint,
    ) {}
}
