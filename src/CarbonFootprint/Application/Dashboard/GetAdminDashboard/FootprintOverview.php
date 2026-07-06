<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard\GetAdminDashboard;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\EventOperations\Domain\Model\Event\Event;
use App\Security\Domain\Model\Account\Account;

final class FootprintOverview
{
    public function __construct(
        public readonly EventCarbonFootprint $eventCarbonFootprint,
        public readonly ?Event $event,
        public readonly ?Account $account,
    ) {}
}
