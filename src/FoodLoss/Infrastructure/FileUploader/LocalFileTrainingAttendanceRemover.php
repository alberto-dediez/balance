<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\FileUploader;

use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceFileRemover;

class LocalFileTrainingAttendanceRemover implements TrainingAttendanceFileRemover
{
    public function __construct(private readonly string $uploadsDir) {}

    public function remove(string $filePath): void
    {
        $absolutePath = $this->uploadsDir . '/' . $filePath;

        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }
}
