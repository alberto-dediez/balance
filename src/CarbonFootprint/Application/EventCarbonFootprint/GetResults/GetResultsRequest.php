<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetResults;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;

final class GetResultsRequest
{
    public function __construct(
        public readonly EventCarbonFootprintId $eventCarbonFootprintId,
    ) {}
}
