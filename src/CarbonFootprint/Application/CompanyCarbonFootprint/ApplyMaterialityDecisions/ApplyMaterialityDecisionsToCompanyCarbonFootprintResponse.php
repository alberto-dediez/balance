<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\ApplyMaterialityDecisions;

use App\Shared\Application\CommandBus\CommandResponse;

class ApplyMaterialityDecisionsToCompanyCarbonFootprintResponse implements CommandResponse
{
    public function __construct(
        public int $excluded,
        public int $included,
    ) {}
}
