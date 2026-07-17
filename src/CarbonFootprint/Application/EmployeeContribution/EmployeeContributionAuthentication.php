<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;

final class EmployeeContributionAuthentication
{
    public function __construct(
        public readonly EmployeeContribution $employeeContribution,
    ) {}
}
