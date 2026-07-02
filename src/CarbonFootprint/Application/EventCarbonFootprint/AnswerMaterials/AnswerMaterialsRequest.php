<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\AnswerMaterials;

use App\CarbonFootprint\Application\Activity\Sync\ActivityRow;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class AnswerMaterialsRequest implements Command
{
    /** @param list<ActivityRow> $rows */
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public array $rows = [],
    ) {}
}
