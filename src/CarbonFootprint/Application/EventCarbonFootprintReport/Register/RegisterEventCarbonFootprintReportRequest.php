<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Register;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class RegisterEventCarbonFootprintReportRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
    ) {}
}
