<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\Show;

use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\PhaseSection;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\Shared\Application\CommandBus\CommandResponse;

class GetProviderContributionDetailResponse implements CommandResponse
{
    /** @param array<string, list<PhaseSection>> $sectionsByPhase keyed by Phase::value */
    public function __construct(
        public ProviderContribution $providerContribution,
        public array $sectionsByPhase,
    ) {}
}
