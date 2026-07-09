<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\AssignProviders;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class AssignProvidersRequest implements Command
{
    /** @param list<AssignedProvider> $providers */
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public array $providers,
    ) {}
}
