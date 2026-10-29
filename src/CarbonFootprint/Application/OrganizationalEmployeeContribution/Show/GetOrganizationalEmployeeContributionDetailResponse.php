<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Show;

use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\Property\Domain\Model\Property\Property;

final class GetOrganizationalEmployeeContributionDetailResponse
{
    /** @param array<string, Mobility> $mobilitiesById */
    public function __construct(
        public readonly OrganizationalEmployeeContribution $contribution,
        public readonly ?Employee $employee,
        public readonly ?Property $workplace,
        public readonly array $mobilitiesById,
    ) {}
}
