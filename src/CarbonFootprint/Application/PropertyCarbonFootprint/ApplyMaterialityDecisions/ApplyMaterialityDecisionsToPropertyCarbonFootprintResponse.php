<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\ApplyMaterialityDecisions;

use App\Shared\Application\CommandBus\CommandResponse;

class ApplyMaterialityDecisionsToPropertyCarbonFootprintResponse implements CommandResponse
{
    public function __construct(
        public int $excluded,
        public int $included,
    ) {}
}
