<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;

class GetCriticalPointDashboardResponse
{
    public function __construct(
        public int $totalCriticalPoints = 0,
        public int $preventionPossibleCriticalPoints = 0,
        public int $eliminationOnlyCriticalPoints = 0,
        public int $coveredProperties = 0,
        public DashboardChart $bySupplyChainStage = new DashboardChart(),
        public DashboardChart $byPriority = new DashboardChart(),
        public DashboardChart $byProperty = new DashboardChart(),
        public DashboardChart $byServiceCategory = new DashboardChart(),
    ) {}

    public function preventionPossiblePercentage(): float
    {
        if (0 === $this->totalCriticalPoints) {
            return 0.0;
        }

        return round($this->preventionPossibleCriticalPoints * 100 / $this->totalCriticalPoints, 1);
    }
}
