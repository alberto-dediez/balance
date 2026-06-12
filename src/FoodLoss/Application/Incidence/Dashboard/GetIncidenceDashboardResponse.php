<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;

class GetIncidenceDashboardResponse
{
    public function __construct(
        public int $totalIncidences = 0,
        public ?string $mostFrequentIncidenceType = null,
        public int $withEvidencesIncidences = 0,
        public int $affectedProperties = 0,
        public DashboardChart $byType = new DashboardChart(),
        public DashboardChart $byProperty = new DashboardChart(),
        public DashboardChart $incidencesByMonth = new DashboardChart(),
        public DashboardChart $surplusByIncidence = new DashboardChart(),
    ) {}

    public function withEvidencesPercentage(): float
    {
        if (0 === $this->totalIncidences) {
            return 0.0;
        }

        return round($this->withEvidencesIncidences * 100 / $this->totalIncidences, 1);
    }
}
