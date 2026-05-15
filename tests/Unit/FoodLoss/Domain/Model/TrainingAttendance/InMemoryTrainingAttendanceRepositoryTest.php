<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;

class InMemoryTrainingAttendanceRepositoryTest extends TrainingAttendanceRepositoryTest
{
    protected function createSUT(): TrainingAttendanceRepository
    {
        return TrainingAttendanceRepositoryMother::inMemory();
    }
}
