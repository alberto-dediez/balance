<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\ApplyMaterialityDecisions;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInput;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class ApplyMaterialityDecisionsToPropertyCarbonFootprintRequest implements Command
{
    /** @param list<MaterialityDecisionInput> $decisions */
    public function __construct(
        public PropertyCarbonFootprintId $propertyCarbonFootprintId,
        public array $decisions,
    ) {}
}
