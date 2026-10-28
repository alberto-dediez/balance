<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Register;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterOrganizationalProviderContributionResponse implements CommandResponse
{
    public function __construct(
        public OrganizationalProviderContributionId $contributionId,
        public ProviderContributionMode $mode,
    ) {}
}
