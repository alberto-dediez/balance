<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionSummary;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\DerivedSourceLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\Emissions;

final class GetCompanyCarbonFootprintScopeResponse
{
    /**
     * @param list<PropertyStageSections>                     $blocks
     * @param array<string, SourceExclusion>                  $exclusions
     * @param array<string, Emissions>                        $derivedEmissions
     * @param list<DerivedSourceLine>                         $derivedLines
     * @param list<OrganizationalProviderContributionSummary> $providerContributions
     * @param list<NonConsolidatedPropertyRow>                $nonConsolidatedProperties
     */
    public function __construct(
        public readonly array $blocks,
        public readonly array $exclusions = [],
        public readonly ?StageExclusionAnswer $stageExclusion = null,
        public readonly array $derivedEmissions = [],
        public readonly array $derivedLines = [],
        public readonly array $nonConsolidatedProperties = [],
        public readonly array $providerContributions = [],
    ) {}
}
