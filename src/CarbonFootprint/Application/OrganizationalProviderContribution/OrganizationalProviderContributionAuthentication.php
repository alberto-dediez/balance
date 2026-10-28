<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\Security\Domain\Model\User\User;

final class OrganizationalProviderContributionAuthentication
{
    public function __construct(
        public readonly OrganizationalProviderContribution $contribution,
        public readonly User $user,
    ) {}
}
