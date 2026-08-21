<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\Export;

use App\EventOperations\Domain\Model\Event\EventId;

class EventAttendeeSurveysExportFilter
{
    public function __construct(
        public ?EventId $eventId = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
    ) {}
}
