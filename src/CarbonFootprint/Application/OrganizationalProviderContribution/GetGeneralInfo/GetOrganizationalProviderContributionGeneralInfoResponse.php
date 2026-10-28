<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\GetGeneralInfo;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;

final class GetOrganizationalProviderContributionGeneralInfoResponse
{
    public function __construct(
        public readonly OrganizationalProviderContribution $contribution,
        public readonly string $subjectName,
        public readonly int $year,
    ) {}
}
