<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Security\Domain\Model\Account\AccountId;

interface TrainingAttendanceUploader
{
    public function upload(string $sourcePath, string $extension, AccountId $accountId, TrainingSessionId $trainingSessionId): string;
}
