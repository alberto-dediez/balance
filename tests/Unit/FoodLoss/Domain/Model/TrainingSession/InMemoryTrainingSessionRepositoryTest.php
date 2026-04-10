<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;

class InMemoryTrainingSessionRepositoryTest extends TrainingSessionRepositoryTest
{
    protected function createSUT(): TrainingSessionRepository
    {
        return TrainingSessionRepositoryMother::inMemory();
    }
}
