<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Dashboard;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;

class GetSurplusDashboardResponse
{
    public function __construct(
        public int $totalSurpluses = 0,
        public float $totalKg = 0.0,
        public float $totalRations = 0.0,
        public DeliveryLogUnit $displayUnit = DeliveryLogUnit::Kg,
        public float $totalQuantity = 0.0,
        public int $recoveredSurpluses = 0,
        public int $eliminatedSurpluses = 0,
        public int $unassessedSurpluses = 0,
        public SurplusDashboardChart $byDestination = new SurplusDashboardChart(),
        public SurplusDashboardChart $byFoodType = new SurplusDashboardChart(),
        public SurplusDashboardChart $byServiceCategory = new SurplusDashboardChart(),
        public SurplusDashboardChart $bySurplusCause = new SurplusDashboardChart(),
        public SurplusDashboardChart $byCriticalPoint = new SurplusDashboardChart(),
        public SurplusDashboardChart $byProperty = new SurplusDashboardChart(),
        public SurplusDashboardChart $quantityByMonth = new SurplusDashboardChart(),
    ) {}

    public function recoveredPercentage(): float
    {
        if (0 === $this->totalSurpluses) {
            return 0.0;
        }

        return round($this->recoveredSurpluses * 100 / $this->totalSurpluses, 1);
    }
}
