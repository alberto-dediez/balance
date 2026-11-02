<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\DeclareIntensityDenominators;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Shared\Application\CommandBus\CommandResponse;

class DeclareIntensityDenominatorsResponse implements CommandResponse
{
    public function __construct(
        public PropertyCarbonFootprint $propertyCarbonFootprint,
    ) {}
}
