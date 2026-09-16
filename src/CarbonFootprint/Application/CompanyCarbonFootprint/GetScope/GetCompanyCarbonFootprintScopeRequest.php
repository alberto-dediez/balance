<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class GetCompanyCarbonFootprintScopeRequest
{
    public function __construct(
        public readonly CompanyCarbonFootprintId $companyCarbonFootprintId,
        public readonly Scope $scope,
    ) {}
}
