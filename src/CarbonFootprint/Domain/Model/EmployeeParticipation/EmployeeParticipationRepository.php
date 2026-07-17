<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;

interface EmployeeParticipationRepository
{
    public function add(EmployeeParticipation $employeeParticipation): void;

    public function find(EmployeeParticipationId $employeeParticipationId): ?EmployeeParticipation;

    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): ?EmployeeParticipation;
}
