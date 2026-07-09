<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\Show;

use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\Shared\Application\CommandBus\Command;

class GetProviderContributionDetailRequest implements Command
{
    public function __construct(
        public ProviderContributionId $providerContributionId,
    ) {}
}
