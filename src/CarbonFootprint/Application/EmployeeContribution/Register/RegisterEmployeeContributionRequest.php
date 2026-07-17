<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\Register;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Shared\Application\CommandBus\Command;

class RegisterEmployeeContributionRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public EmployeeId $employeeId,
    ) {}
}
