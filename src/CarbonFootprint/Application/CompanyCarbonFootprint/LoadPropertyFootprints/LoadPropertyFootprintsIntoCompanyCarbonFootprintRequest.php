<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Application\CommandBus\Command;

class LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest implements Command
{
    public function __construct(
        public CompanyCarbonFootprintId $companyCarbonFootprintId,
        public Scope $scope,
    ) {}
}
