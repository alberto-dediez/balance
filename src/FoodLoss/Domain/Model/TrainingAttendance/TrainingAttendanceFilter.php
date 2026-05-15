<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class TrainingAttendanceFilter
{
    use Filter;

    public function __construct(
        public ?TrainingSessionId $trainingSessionId = null,
        public ?EmployeeId $employeeId = null,
        public ?AccountId $accountId = null,
        public ?string $filePath = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
