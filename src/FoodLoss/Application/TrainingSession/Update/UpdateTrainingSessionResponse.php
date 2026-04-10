<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\Update;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateTrainingSessionResponse implements CommandResponse
{
    public function __construct(
        public TrainingSessionId $trainingSessionId,
        public string $title,
    ) {}
}
