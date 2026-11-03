<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\ApplyMaterialityDecisions;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInput;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class ApplyMaterialityDecisionsToCompanyCarbonFootprintRequest implements Command
{
    /** @param list<MaterialityDecisionInput> $decisions */
    public function __construct(
        public CompanyCarbonFootprintId $companyCarbonFootprintId,
        public array $decisions,
    ) {}
}
