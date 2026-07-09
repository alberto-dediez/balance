<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution;

use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\Security\Domain\Model\User\User;

final class ProviderContributionAuthentication
{
    public function __construct(
        public readonly ProviderContribution $providerContribution,
        public readonly User $user,
    ) {}
}
