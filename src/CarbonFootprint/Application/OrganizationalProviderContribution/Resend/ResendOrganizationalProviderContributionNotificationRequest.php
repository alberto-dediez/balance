<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Resend;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\Shared\Application\CommandBus\Command;

class ResendOrganizationalProviderContributionNotificationRequest implements Command
{
    public function __construct(
        public OrganizationalProviderContributionId $contributionId,
    ) {}
}
