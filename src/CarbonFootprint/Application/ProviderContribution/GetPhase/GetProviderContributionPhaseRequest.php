<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\GetPhase;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;

final class GetProviderContributionPhaseRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly Phase $phase,
    ) {}
}
