<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\Register;

use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Application\CommandBus\Command;

class RegisterAttendeeSurveyRequest implements Command
{
    public function __construct(
        public EventId $eventId,
    ) {}
}
