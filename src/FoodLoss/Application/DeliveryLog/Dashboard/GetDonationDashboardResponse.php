<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;

class GetDonationDashboardResponse
{
    public function __construct(
        public int $totalDeliveryLogs = 0,
        public float $totalKg = 0.0,
        public float $totalRations = 0.0,
        public DeliveryLogUnit $displayUnit = DeliveryLogUnit::Kg,
        public float $totalQuantity = 0.0,
        public int $activeDonationAgreements = 0,
        public int $expiringSoonDonationAgreements = 0,
        public int $totalRecipientOrganizations = 0,
        public DashboardChart $quantityByMonth = new DashboardChart(),
        public DashboardChart $byProductType = new DashboardChart(),
        public DashboardChart $byRecipientOrganization = new DashboardChart(),
        public DashboardChart $byProperty = new DashboardChart(),
        public DashboardChart $agreementsByStatus = new DashboardChart(),
        public DashboardChart $organizationsByType = new DashboardChart(),
    ) {}
}
