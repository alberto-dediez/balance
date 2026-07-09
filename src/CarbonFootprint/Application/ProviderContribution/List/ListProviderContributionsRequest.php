<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\List;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class ListProviderContributionsRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
    ) {}
}
