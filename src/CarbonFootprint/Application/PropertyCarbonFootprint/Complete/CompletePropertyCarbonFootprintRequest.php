<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\Complete;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class CompletePropertyCarbonFootprintRequest implements Command
{
    public function __construct(
        public PropertyCarbonFootprintId $propertyCarbonFootprintId,
    ) {}
}
