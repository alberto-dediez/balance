<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Export;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;

class EventCarbonFootprintDetailExportFilter
{
    public function __construct(
        public ?EventCarbonFootprintId $eventCarbonFootprintId = null,
    ) {}
}
