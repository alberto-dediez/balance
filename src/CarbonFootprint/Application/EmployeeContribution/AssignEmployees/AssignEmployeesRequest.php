<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\AssignEmployees;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class AssignEmployeesRequest implements Command
{
    /** @param list<AssignedEmployee> $employees */
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public array $employees,
        public ?int $totalEmployees,
        public ?ConfidenceLevel $confidenceLevel,
    ) {}
}
