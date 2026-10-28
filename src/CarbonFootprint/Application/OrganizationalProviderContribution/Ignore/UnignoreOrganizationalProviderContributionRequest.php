<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Ignore;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\Shared\Application\CommandBus\Command;

class UnignoreOrganizationalProviderContributionRequest implements Command
{
    public function __construct(
        public OrganizationalProviderContributionId $contributionId,
    ) {}
}
