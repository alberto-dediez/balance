<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\FileUploader;

use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceUploader;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Security\Domain\Model\Account\AccountId;

class LocalFileTrainingAttendanceUploader implements TrainingAttendanceUploader
{
    public function __construct(private readonly string $uploadsDir) {}

    public function upload(string $sourcePath, string $extension, AccountId $accountId, TrainingSessionId $trainingSessionId): string
    {
        $fileName = $trainingSessionId . '-' . uniqid() . '.' . $extension;
        $relativePath = 'training_attendances/' . $accountId . '/' . $fileName;
        $absoluteDestination = $this->uploadsDir . '/' . $relativePath;

        if (!is_dir(dirname($absoluteDestination))) {
            mkdir(dirname($absoluteDestination), 0755, true);
        }

        rename($sourcePath, $absoluteDestination);

        return $relativePath;
    }
}
