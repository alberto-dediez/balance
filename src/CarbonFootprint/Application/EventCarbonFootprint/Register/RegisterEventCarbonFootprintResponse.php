<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Register;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterEventCarbonFootprintResponse implements CommandResponse
{
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public EventId $eventId,
    ) {}
}
