<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Emissions;

final class OrganizationalCarbonFootprintReportData implements CarbonFootprintReportData
{
    /**
     * @param array<string, Emissions>                                             $emissionsBySource
     * @param array<string, Emissions>                                             $emissionsByScope
     * @param array<string, array<string, Emissions>>                              $emissionsByScopeAndSource
     * @param list<array{propertyId: ?string, name: string, emissions: Emissions}> $emissionsByProperty
     * @param list<OrganizationalCarbonFootprintReportActivityLine>                $activityLines
     * @param list<OrganizationalCarbonFootprintReportExclusionLine>               $exclusionLines
     */
    public function __construct(
        public readonly CarbonFootprintReportSubjectType $subjectType,
        public readonly string $subjectName,
        public readonly ?string $companyName,
        public readonly ?string $countryName,
        public readonly string $emissionFactorGroupName,
        public readonly int $calculationYear,
        public readonly Emissions $totalEmissions,
        public readonly Emissions $locationBasedElectricityEmissions,
        public readonly array $emissionsBySource,
        public readonly array $emissionsByScope,
        public readonly array $emissionsByScopeAndSource,
        public readonly array $emissionsByProperty,
        public readonly array $activityLines,
        public readonly array $exclusionLines = [],
        public readonly ?OrganizationalCarbonFootprintReportMitigationPlan $mitigationPlan = null,
    ) {}

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function subjectName(): string
    {
        return $this->subjectName;
    }

    public function calculationYear(): int
    {
        return $this->calculationYear;
    }

    public function totalEmissions(): Emissions
    {
        return $this->totalEmissions;
    }
}
