<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingSession;

interface TrainingSessionRepository
{
    public function add(TrainingSession $trainingSession): void;

    public function remove(TrainingSession $trainingSession): void;

    public function find(TrainingSessionId $trainingSessionId): ?TrainingSession;

    /** @return array<TrainingSession> */
    public function findByFilter(TrainingSessionFilter $filter): array;

    public function totalResultsByFilter(TrainingSessionFilter $filter): int;
}
