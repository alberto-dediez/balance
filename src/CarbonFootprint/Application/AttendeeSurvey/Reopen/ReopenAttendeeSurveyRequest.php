<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\Reopen;

use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Application\CommandBus\Command;

class ReopenAttendeeSurveyRequest implements Command
{
    public function __construct(
        public EventId $eventId,
    ) {}
}
