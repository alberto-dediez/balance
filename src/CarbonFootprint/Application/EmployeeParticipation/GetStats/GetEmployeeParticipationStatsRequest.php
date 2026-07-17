<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeParticipation\GetStats;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class GetEmployeeParticipationStatsRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
    ) {}
}
