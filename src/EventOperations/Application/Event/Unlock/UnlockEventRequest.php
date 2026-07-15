<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\Unlock;

use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Application\CommandBus\Command;

class UnlockEventRequest implements Command
{
    public function __construct(
        public EventId $eventId,
    ) {}
}
