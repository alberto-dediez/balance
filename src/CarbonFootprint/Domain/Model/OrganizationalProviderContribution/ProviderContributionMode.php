<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

enum ProviderContributionMode: string
{
    case Manual    = 'manual';
    case Requested = 'requested';

    public function isManual(): bool
    {
        return self::Manual === $this;
    }

    public function isRequested(): bool
    {
        return self::Requested === $this;
    }
}
