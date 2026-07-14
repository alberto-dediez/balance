<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Update;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class UpdateEventCarbonFootprintRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public ?\DateTimeImmutable $calculationPeriodStartDate = null,
        public ?\DateTimeImmutable $calculationPeriodEndDate = null,
    ) {}
}
