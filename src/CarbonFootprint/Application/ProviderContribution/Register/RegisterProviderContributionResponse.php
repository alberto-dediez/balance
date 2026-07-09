<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\Register;

use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterProviderContributionResponse implements CommandResponse
{
    public function __construct(
        public ProviderContributionId $providerContributionId,
    ) {}
}
