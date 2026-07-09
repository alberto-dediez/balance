<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\Register;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Shared\Application\CommandBus\Command;

class RegisterProviderContributionRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public ProviderId $providerId,
    ) {}
}
