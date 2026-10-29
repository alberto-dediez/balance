<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Ignore;

use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionId;
use App\Shared\Application\CommandBus\Command;

class IgnoreOrganizationalEmployeeContributionRequest implements Command
{
    public function __construct(
        public OrganizationalEmployeeContributionId $contributionId,
    ) {}
}
