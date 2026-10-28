<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Remove;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\Shared\Application\CommandBus\Command;

class RemoveOrganizationalProviderContributionRequest implements Command
{
    public function __construct(
        public OrganizationalProviderContributionId $contributionId,
    ) {}
}
