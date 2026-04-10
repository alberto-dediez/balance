<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\Remove;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Shared\Application\CommandBus\Command;

class RemoveTrainingSessionRequest implements Command
{
    public function __construct(public TrainingSessionId $trainingSessionId) {}
}
