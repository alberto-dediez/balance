<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\Ignore;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\Shared\Application\CommandBus\Command;

class UnignoreEmployeeContributionRequest implements Command
{
    public function __construct(
        public EmployeeContributionId $employeeContributionId,
    ) {}
}
