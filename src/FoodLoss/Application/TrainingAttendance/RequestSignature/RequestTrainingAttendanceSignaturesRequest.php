<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\RequestSignature;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RequestTrainingAttendanceSignaturesRequest implements Command
{
    /** @param array<EmployeeId> $employeeIds */
    public function __construct(
        public ?TrainingSessionId $trainingSessionId = null,
        public array $employeeIds = [],
        public ?AccountId $accountId = null,
    ) {}
}
