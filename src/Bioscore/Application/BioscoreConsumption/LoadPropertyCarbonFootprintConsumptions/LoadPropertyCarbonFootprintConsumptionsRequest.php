<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreConsumption\LoadPropertyCarbonFootprintConsumptions;

use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class LoadPropertyCarbonFootprintConsumptionsRequest implements Command
{
    public function __construct(
        public PropertyCarbonFootprintId $propertyCarbonFootprintId,
        public Scope $scope,
    ) {}
}
