<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\Update;

use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventSector;
use App\EventOperations\Domain\Model\Event\EventSize;
use App\EventOperations\Domain\Model\Event\EventTypology;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Application\CommandBus\Command;

class UpdateEventRequest implements Command
{
    public function __construct(
        public EventId $eventId,
        public string $name,
        public ?DestinationId $location = null,
        public ?EventSize $size = null,
        public ?int $estimatedAttendees = null,
        public ?EventSector $sector = null,
        public ?EventTypology $typology = null,
        public ?\DateTimeImmutable $setupStartDate = null,
        public ?\DateTimeImmutable $setupEndDate = null,
        public ?\DateTimeImmutable $celebrationStartDate = null,
        public ?\DateTimeImmutable $celebrationEndDate = null,
        public ?\DateTimeImmutable $dismantlingStartDate = null,
        public ?\DateTimeImmutable $dismantlingEndDate = null,
    ) {}
}
