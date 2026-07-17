<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;

interface EmployeeContributionRepository
{
    public function add(EmployeeContribution $employeeContribution): void;

    public function find(EmployeeContributionId $employeeContributionId): ?EmployeeContribution;

    public function findByToken(#[\SensitiveParameter] string $token): ?EmployeeContribution;

    /** @return list<EmployeeContribution> */
    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): array;

    public function findByEventCarbonFootprintIdAndEmployeeId(EventCarbonFootprintId $eventCarbonFootprintId, EmployeeId $employeeId): ?EmployeeContribution;
}
