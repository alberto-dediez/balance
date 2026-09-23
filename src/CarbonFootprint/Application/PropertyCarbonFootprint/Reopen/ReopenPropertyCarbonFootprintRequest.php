<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class ReopenPropertyCarbonFootprintRequest implements Command
{
    public function __construct(
        public PropertyCarbonFootprintId $propertyCarbonFootprintId,
    ) {}
}
