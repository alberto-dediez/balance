<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryTrainingSessionRepository;

class TrainingSessionRepositoryMother
{
    public static function inMemory(): InMemoryTrainingSessionRepository
    {
        return new InMemoryTrainingSessionRepository();
    }
}
