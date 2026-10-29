<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\HumanResources\Domain\Model\Employee\EmployeeId;

interface OrganizationalEmployeeContributionRepository
{
    public function add(OrganizationalEmployeeContribution $contribution): void;

    public function find(OrganizationalEmployeeContributionId $contributionId): ?OrganizationalEmployeeContribution;

    public function findByToken(#[\SensitiveParameter] string $token): ?OrganizationalEmployeeContribution;

    /** @return list<OrganizationalEmployeeContribution> */
    public function findByCarbonFootprint(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): array;

    public function findByCarbonFootprintAndEmployee(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId, EmployeeId $employeeId): ?OrganizationalEmployeeContribution;
}
