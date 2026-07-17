<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeParticipation\Reopen;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class ReopenEmployeeParticipationRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
    ) {}
}
