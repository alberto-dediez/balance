<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\Register;

use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterEventResponse implements CommandResponse
{
    public function __construct(
        public EventId $eventId,
        public string $name,
    ) {}
}
