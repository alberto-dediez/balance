<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeParticipation\GetStats;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipation;
use App\Shared\Application\CommandBus\CommandResponse;

class GetEmployeeParticipationStatsResponse implements CommandResponse
{
    public function __construct(
        public EmployeeParticipation $employeeParticipation,
        public int $pendingCount,
        public int $answeredCount,
        public int $ignoredCount,
        public ?float $answeredPercentage,
    ) {}
}
