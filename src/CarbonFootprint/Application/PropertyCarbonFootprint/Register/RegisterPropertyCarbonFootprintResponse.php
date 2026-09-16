<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\Register;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterPropertyCarbonFootprintResponse implements CommandResponse
{
    public function __construct(
        public PropertyCarbonFootprintId $propertyCarbonFootprintId,
    ) {}
}
