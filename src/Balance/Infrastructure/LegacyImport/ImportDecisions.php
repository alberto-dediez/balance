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
        public array $organizations = [],
        public array $mobilities = [],
        public array $projects = [],
    ) {}

    public function organization(string $slug): ?OrganizationDecision
    {
        return $this->organizations[$slug] ?? null;
    }
}
