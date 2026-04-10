<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\Update;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Shared\Application\CommandBus\Command;

class UpdateTrainingSessionRequest implements Command
{
    public function __construct(
        public TrainingSessionId $trainingSessionId,
        public \DateTimeImmutable $sessionDate,
        public string $title,
        public string $topic,
    ) {}
}
