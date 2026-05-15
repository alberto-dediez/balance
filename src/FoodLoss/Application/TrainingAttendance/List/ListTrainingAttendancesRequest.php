<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\List;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class ListTrainingAttendancesRequest implements Command
{
    public function __construct(
        public ?TrainingSessionId $trainingSessionId = null,
        public ?AccountId $accountId = null,
    ) {}
}
