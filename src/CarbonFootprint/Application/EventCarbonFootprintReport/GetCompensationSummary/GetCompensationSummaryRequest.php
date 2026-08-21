<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\GetCompensationSummary;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class GetCompensationSummaryRequest implements Command
{
    public function __construct(public readonly EventCarbonFootprintId $eventCarbonFootprintId) {}
}
