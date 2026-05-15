<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryTrainingAttendanceRepository;

class TrainingAttendanceRepositoryMother
{
    public static function inMemory(): InMemoryTrainingAttendanceRepository
    {
        return new InMemoryTrainingAttendanceRepository();
    }
}
