<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Show;

use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionId;

final class GetOrganizationalEmployeeContributionDetailRequest
{
    public function __construct(
        public readonly OrganizationalEmployeeContributionId $contributionId,
    ) {}
}
