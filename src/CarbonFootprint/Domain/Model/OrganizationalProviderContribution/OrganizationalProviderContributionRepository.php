<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\EventOperations\Domain\Model\Provider\ProviderId;

interface OrganizationalProviderContributionRepository
{
    public function add(OrganizationalProviderContribution $contribution): void;

    public function find(OrganizationalProviderContributionId $contributionId): ?OrganizationalProviderContribution;

    public function findByToken(#[\SensitiveParameter] string $token): ?OrganizationalProviderContribution;

    /** @return list<OrganizationalProviderContribution> */
    public function findActiveByFootprint(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): array;

    public function findActiveByFootprintAndProvider(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        ProviderId $providerId,
    ): ?OrganizationalProviderContribution;

    /** @return array<OrganizationalProviderContribution> */
    public function findByFilter(OrganizationalProviderContributionFilter $filter): array;

    public function totalResultsByFilter(OrganizationalProviderContributionFilter $filter): int;
}
