<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;

class GetTrainingDashboardResponse
{
    public function __construct(
        public int $totalTrainingSessions = 0,
        public int $totalTrainingAttendances = 0,
        public int $totalProtocols = 0,
        public int $readProtocols = 0,
        public int $totalProtocolAccesses = 0,
        public DashboardChart $attendancesBySession = new DashboardChart(),
        public DashboardChart $sessionsByMonth = new DashboardChart(),
        public DashboardChart $accessesByProtocol = new DashboardChart(),
        public DashboardChart $accessesByMonth = new DashboardChart(),
    ) {}

    public function averageAttendancesPerSession(): float
    {
        if (0 === $this->totalTrainingSessions) {
            return 0.0;
        }

        return round($this->totalTrainingAttendances / $this->totalTrainingSessions, 1);
    }

    public function readProtocolsPercentage(): float
    {
        if (0 === $this->totalProtocols) {
            return 0.0;
        }

        return round($this->readProtocols * 100 / $this->totalProtocols, 1);
    }
}
