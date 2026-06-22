<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\HumanResources\Domain\Model\Employee\Employee;

interface TrainingSessionPdfGenerator
{
    public function generate(TrainingSession $session, Employee $employee): string;
}
