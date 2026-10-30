<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\ChangePrecalculatedMode;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class ChangePropertyCarbonFootprintPrecalculatedModeRequest implements Command
{
    public function __construct(
        public PropertyCarbonFootprintId $propertyCarbonFootprintId,
        public bool $precalculated,
    ) {}
}
