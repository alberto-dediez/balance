<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\ChangeSources;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\Shared\Application\CommandBus\Command;

class ChangeOrganizationalProviderContributionSourcesRequest implements Command
{
    /** @param list<Source> $sources */
    public function __construct(
        public OrganizationalProviderContributionId $contributionId,
        public array $sources,
    ) {}
}
