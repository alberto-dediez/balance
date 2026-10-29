<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;

final class OrganizationalEmployeeContributionAuthentication
{
    public function __construct(
        public readonly OrganizationalEmployeeContribution $contribution,
    ) {}
}
