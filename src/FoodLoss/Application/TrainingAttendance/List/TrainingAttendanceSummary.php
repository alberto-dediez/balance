<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\List;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\HumanResources\Domain\Model\Employee\Employee;

class TrainingAttendanceSummary
{
    /** @param array<TrainingAttendance> $attendances */
    public function __construct(
        public Employee $employee,
        public array $attendances,
    ) {}

    public function hasAttended(): bool
    {
        return count($this->attendances) > 0;
    }
}
