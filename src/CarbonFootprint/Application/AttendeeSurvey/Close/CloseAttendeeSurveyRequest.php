<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\Close;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class CloseAttendeeSurveyRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public int $realAttendees,
        public ConfidenceLevel $confidenceLevel,
    ) {}
}
