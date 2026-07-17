<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\List;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\Shared\Application\CommandBus\CommandResponse;

class ListEmployeeContributionsResponse implements CommandResponse
{
    /** @param list<EmployeeContribution> $employeeContributions */
    public function __construct(
        public array $employeeContributions,
    ) {}
}
