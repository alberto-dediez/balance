<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Register;

use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterOrganizationalEmployeeContributionResponse implements CommandResponse
{
    public function __construct(
        public OrganizationalEmployeeContribution $contribution,
    ) {}
}
