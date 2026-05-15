<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance;

interface TrainingAttendanceFileRemover
{
    public function remove(string $filePath): void;
}
