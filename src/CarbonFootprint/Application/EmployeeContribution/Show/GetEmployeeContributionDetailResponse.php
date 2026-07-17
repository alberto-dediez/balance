<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\Show;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\Shared\Application\CommandBus\CommandResponse;

class GetEmployeeContributionDetailResponse implements CommandResponse
{
    public function __construct(
        public EmployeeContribution $employeeContribution,
    ) {}
}
