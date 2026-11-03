<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\ClearMaterialityDecisions;

use App\Shared\Application\CommandBus\CommandResponse;

class ClearMaterialityDecisionsFromPropertyCarbonFootprintResponse implements CommandResponse
{
    public function __construct(
        public bool $cleared,
    ) {}
}
