<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\GetPhase;

use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\PhaseSection;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;

final class GetProviderContributionPhaseResponse
{
    /** @param list<PhaseSection> $sections */
    public function __construct(
        public readonly ProviderContribution $providerContribution,
        public readonly array $sections,
    ) {}
}
