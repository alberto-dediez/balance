<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Show;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;

final class GetOrganizationalProviderContributionDetailRequest
{
    public function __construct(
        public readonly OrganizationalProviderContributionId $contributionId,
    ) {}
}
