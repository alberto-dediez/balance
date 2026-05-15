<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\Remove;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\Shared\Application\CommandBus\Command;

class RemoveTrainingAttendanceRequest implements Command
{
    public function __construct(
        public TrainingAttendanceId $trainingAttendanceId,
    ) {}
}
