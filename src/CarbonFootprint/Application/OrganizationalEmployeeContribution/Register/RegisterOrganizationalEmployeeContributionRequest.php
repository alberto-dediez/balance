<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Register;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Shared\Application\CommandBus\Command;

class RegisterOrganizationalEmployeeContributionRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $carbonFootprintId,
        public EmployeeId $employeeId,
    ) {}
}
