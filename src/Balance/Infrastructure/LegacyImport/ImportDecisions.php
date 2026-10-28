<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

use App\Balance\Domain\Model\Project\ProjectId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

final class ImportDecisions
{
    /**
     * @param array<string, OrganizationDecision> $organizations
     * @param array<string, MobilityId>           $mobilities
     * @param array<string, ProjectId|null>       $projects
     */
    public function __construct(
        public string $legacyUrl,
        public LegacyGroups $groups,
        public array $organizations = [],
        public array $mobilities = [],
        public array $projects = [],
        public ?string $consultantOrganizationId = null,
    ) {}

    public function organization(string $organizationId): ?OrganizationDecision
    {
        return $this->organizations[$organizationId] ?? null;
    }

    public function consultantDecision(): ?OrganizationDecision
    {
        return null !== $this->consultantOrganizationId ? $this->organization($this->consultantOrganizationId) : null;
    }

    /** @return list<OrganizationDecision> */
    public function decisionsOfAccountKey(string $key): array
    {
        return array_values(array_filter(
            $this->organizations,
            static fn(OrganizationDecision $decision): bool => $decision->accountKey === $key,
        ));
    }
}
