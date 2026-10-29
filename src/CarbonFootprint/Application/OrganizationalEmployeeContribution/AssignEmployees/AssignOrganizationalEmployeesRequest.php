<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AssignEmployees;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Shared\Application\CommandBus\Command;

class AssignOrganizationalEmployeesRequest implements Command
{
    /** @param list<EmployeeId> $employeeIds */
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $carbonFootprintId,
        public array $employeeIds,
        public int $totalEmployees,
        public ConfidenceLevel $confidenceLevel,
    ) {}
}
