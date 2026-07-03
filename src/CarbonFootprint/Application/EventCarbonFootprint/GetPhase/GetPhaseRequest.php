<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;

final class GetPhaseRequest
{
    public function __construct(
        public readonly EventCarbonFootprintId $eventCarbonFootprintId,
        public readonly Phase $phase,
    ) {}
}
