<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\Show;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\Shared\Application\CommandBus\CommandResponse;

class GetProviderContributionDetailResponse implements CommandResponse
{
    /** @param array<string, list<StageSection>> $sectionsByPhase keyed by Phase::value */
    public function __construct(
        public ProviderContribution $providerContribution,
        public array $sectionsByPhase,
    ) {}
}
