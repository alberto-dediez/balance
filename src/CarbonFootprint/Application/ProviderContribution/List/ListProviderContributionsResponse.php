<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\List;

use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\Shared\Application\CommandBus\CommandResponse;

class ListProviderContributionsResponse implements CommandResponse
{
    /** @param list<ProviderContribution> $providerContributions */
    public function __construct(
        public array $providerContributions,
    ) {}
}
