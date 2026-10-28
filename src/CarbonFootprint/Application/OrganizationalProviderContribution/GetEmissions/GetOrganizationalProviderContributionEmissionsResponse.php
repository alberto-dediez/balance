<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\GetEmissions;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;

final class GetOrganizationalProviderContributionEmissionsResponse
{
    /** @param array<string, list<StageSection>> $sectionsBySource */
    public function __construct(
        public readonly OrganizationalProviderContribution $contribution,
        public readonly array $sectionsBySource,
    ) {}
}
