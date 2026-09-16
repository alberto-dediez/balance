<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\GetPhase;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;

final class GetProviderContributionPhaseResponse
{
    /** @param list<StageSection> $sections */
    public function __construct(
        public readonly ProviderContribution $providerContribution,
        public readonly array $sections,
    ) {}
}
