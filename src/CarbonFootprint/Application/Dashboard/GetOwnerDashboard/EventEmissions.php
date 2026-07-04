<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard;

use App\EventOperations\Domain\Model\Event\Event;

final class EventEmissions
{
    public function __construct(
        public readonly Event $event,
        public readonly float $co2eq,
    ) {}
}
