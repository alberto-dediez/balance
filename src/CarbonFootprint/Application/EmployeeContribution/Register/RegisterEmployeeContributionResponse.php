<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\Register;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterEmployeeContributionResponse implements CommandResponse
{
    public function __construct(
        public EmployeeContributionId $employeeContributionId,
    ) {}
}
