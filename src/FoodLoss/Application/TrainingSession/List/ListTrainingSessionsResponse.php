<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\List;

class ListTrainingSessionsResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\TrainingSession\TrainingSession> $trainingSessions */
    public function __construct(public array $trainingSessions = [], public int $totalResults = 0) {}
}
