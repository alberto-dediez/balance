<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Dashboard;

class GetAdminDashboardResponse
{
    public function __construct(
        public int $totalAccounts = 0,
        public int $activeAccounts = 0,
        public int $totalUsers = 0,
        public int $totalEmployees = 0,
        public int $totalProperties = 0,
        public int $totalCompanies = 0,
        public int $totalActions = 0,
        public int $totalReasons = 0,
        public int $totalServiceCategories = 0,
        public int $totalSurplusCauses = 0,
        public DashboardChart $actionsByPriority = new DashboardChart(),
    ) {}
}
