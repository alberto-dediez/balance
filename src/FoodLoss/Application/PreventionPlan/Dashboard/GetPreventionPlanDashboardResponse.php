<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;

class GetPreventionPlanDashboardResponse
{
    public function __construct(
        public int $totalPreventionPlans = 0,
        public int $completedPreventionPlans = 0,
        public int $inProgressPreventionPlans = 0,
        public int $withResponsiblePreventionPlans = 0,
        public DashboardChart $byStatus = new DashboardChart(),
        public DashboardChart $registrationsByMonth = new DashboardChart(),
        public DashboardChart $byCompany = new DashboardChart(),
        public DashboardChart $properties = new DashboardChart(),
    ) {}

    public function completedPercentage(): float
    {
        if (0 === $this->totalPreventionPlans) {
            return 0.0;
        }

        return round($this->completedPreventionPlans * 100 / $this->totalPreventionPlans, 1);
    }

    public function withResponsiblePercentage(): float
    {
        if (0 === $this->totalPreventionPlans) {
            return 0.0;
        }

        return round($this->withResponsiblePreventionPlans * 100 / $this->totalPreventionPlans, 1);
    }
}
