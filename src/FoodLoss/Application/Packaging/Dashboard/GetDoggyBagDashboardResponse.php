<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;

class GetDoggyBagDashboardResponse
{
    public function __construct(
        public int $totalPackagings = 0,
        public int $totalStock = 0,
        public int $totalDoggyBagSigns = 0,
        public int $totalDoggyBagExceptions = 0,
        public DashboardChart $stockByType = new DashboardChart(),
        public DashboardChart $stockByProperty = new DashboardChart(),
        public DashboardChart $signsByProperty = new DashboardChart(),
        public DashboardChart $exceptionsByCriticalPoint = new DashboardChart(),
        public DashboardChart $exceptionsByMonth = new DashboardChart(),
    ) {}
}
