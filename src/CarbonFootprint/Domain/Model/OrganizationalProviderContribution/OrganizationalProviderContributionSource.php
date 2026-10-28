<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;

class OrganizationalProviderContributionSource
{
    public function __construct(
        private OrganizationalProviderContributionSourceId $id,
        private OrganizationalProviderContribution $contribution,
        private Source $source,
    ) {}

    public function id(): OrganizationalProviderContributionSourceId
    {
        return $this->id;
    }

    public function contribution(): OrganizationalProviderContribution
    {
        return $this->contribution;
    }

    public function source(): Source
    {
        return $this->source;
    }
}
