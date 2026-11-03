<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\ClearMaterialityDecisions;

use App\Shared\Application\CommandBus\CommandResponse;

class ClearMaterialityDecisionsFromCompanyCarbonFootprintResponse implements CommandResponse
{
    public function __construct(
        public bool $cleared,
    ) {}
}
