<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\List;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionSummary;

final class ListOrganizationalProviderContributionsResponse
{
    /** @param list<OrganizationalProviderContributionSummary> $contributions */
    public function __construct(
        public readonly array $contributions,
    ) {}

    public function pendingCount(): int
    {
        return count(array_filter(
            $this->contributions,
            static fn(OrganizationalProviderContributionSummary $summary): bool => $summary->contribution->status()->isPending(),
        ));
    }
}
