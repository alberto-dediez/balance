<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\AnswerAssembly;

use App\CarbonFootprint\Application\Activity\Sync\ActivityRow;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class AnswerAssemblyRequest implements Command
{
    /**
     * @param list<ActivityRow> $fixedFacilitiesRows
     * @param list<ActivityRow> $freightTransportRows
     * @param list<ActivityRow> $machineryOperationRows
     * @param list<ActivityRow> $buildingElectricityRows
     * @param list<ActivityRow> $accommodationRows
     * @param list<ActivityRow> $mobilityRows
     * @param list<ActivityRow> $wasteManagementRows
     */
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public array $fixedFacilitiesRows = [],
        public array $freightTransportRows = [],
        public array $machineryOperationRows = [],
        public array $buildingElectricityRows = [],
        public array $accommodationRows = [],
        public array $mobilityRows = [],
        public array $wasteManagementRows = [],
    ) {}
}
