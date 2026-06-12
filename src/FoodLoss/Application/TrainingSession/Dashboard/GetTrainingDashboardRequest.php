<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\Dashboard;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionFilter;

class GetTrainingDashboardRequest extends TrainingSessionFilter
{
    public ?\DateTimeImmutable $dateFrom = null;

    public ?\DateTimeImmutable $dateTo = null;
}
