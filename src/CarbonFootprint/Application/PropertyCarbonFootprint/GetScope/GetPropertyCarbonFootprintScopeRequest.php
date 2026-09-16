<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope;

use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;

final class GetPropertyCarbonFootprintScopeRequest
{
    public function __construct(
        public readonly PropertyCarbonFootprintId $propertyCarbonFootprintId,
        public readonly Scope $scope,
    ) {}
}
