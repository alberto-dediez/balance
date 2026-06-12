<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Dashboard;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;

class GetFoodLossDashboardResponse
{
    public function __construct(
        public int $totalCriticalPoints = 0,
        public DashboardChart $criticalPointsByPriority = new DashboardChart(),
        public DashboardChart $criticalPointsBySupplyChainStage = new DashboardChart(),
        public int $totalPreventionPlans = 0,
        public bool $hasCompletedPreventionPlan = false,
        public DashboardChart $preventionPlansByStatus = new DashboardChart(),
        public int $totalSurpluses = 0,
        public float $surplusKg = 0.0,
        public float $surplusRations = 0.0,
        public DeliveryLogUnit $displayUnit = DeliveryLogUnit::Kg,
        public float $surplusTotalQuantity = 0.0,
        public int $recoveredSurpluses = 0,
        public int $eliminatedSurpluses = 0,
        public int $unassessedSurpluses = 0,
        public DashboardChart $surplusesByDestination = new DashboardChart(),
        public int $activeDonationAgreements = 0,
        public int $expiringSoonDonationAgreements = 0,
        public int $expiredDonationAgreements = 0,
        public int $totalDeliveryLogs = 0,
        public float $deliveredKg = 0.0,
        public float $deliveredRations = 0.0,
        public float $deliveredTotalQuantity = 0.0,
        public int $totalPackagings = 0,
        public int $totalPackagingStock = 0,
        public DashboardChart $packagingStockByType = new DashboardChart(),
        public int $totalDoggyBagSigns = 0,
        public int $totalProtocols = 0,
        public int $readProtocols = 0,
        public int $totalProtocolAccesses = 0,
        public int $totalTrainingSessions = 0,
        public int $totalTrainingAttendances = 0,
        public int $totalIncidences = 0,
        public DashboardChart $incidencesByType = new DashboardChart(),
    ) {}

    public function recoveredPercentage(): float
    {
        if (0 === $this->totalSurpluses) {
            return 0.0;
        }

        return round($this->recoveredSurpluses * 100 / $this->totalSurpluses, 1);
    }

    public function unreadProtocols(): int
    {
        return $this->totalProtocols - $this->readProtocols;
    }

    public function readProtocolsPercentage(): float
    {
        if (0 === $this->totalProtocols) {
            return 0.0;
        }

        return round($this->readProtocols * 100 / $this->totalProtocols, 1);
    }
}
