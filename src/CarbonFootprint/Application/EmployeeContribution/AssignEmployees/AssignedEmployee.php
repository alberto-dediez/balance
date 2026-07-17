<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\AssignEmployees;

use App\HumanResources\Domain\Model\Employee\EmployeeId;

class AssignedEmployee
{
    public function __construct(
        public EmployeeId $employeeId,
    ) {}
}
