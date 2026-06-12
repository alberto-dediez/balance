<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;
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
        public DashboardChart $byDestination = new DashboardChart(),
        public DashboardChart $byFoodType = new DashboardChart(),
        public DashboardChart $byServiceCategory = new DashboardChart(),
        public DashboardChart $bySurplusCause = new DashboardChart(),
        public DashboardChart $byCriticalPoint = new DashboardChart(),
        public DashboardChart $byProperty = new DashboardChart(),
        public DashboardChart $quantityByMonth = new DashboardChart(),
        public int $totalWasteManagers = 0,
        public int $animalFeedAuthorizedWasteManagers = 0,
        public int $compostAuthorizedWasteManagers = 0,
    ) {}

    public function recoveredPercentage(): float
    {
        if (0 === $this->totalSurpluses) {
            return 0.0;
        }

        return round($this->recoveredSurpluses * 100 / $this->totalSurpluses, 1);
    }
}
