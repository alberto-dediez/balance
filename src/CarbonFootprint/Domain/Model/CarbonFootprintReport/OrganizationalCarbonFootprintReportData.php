<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\DerivedSourceLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\OperatingRegime;

final class OrganizationalCarbonFootprintReportData implements CarbonFootprintReportData
{
    /**
     * @param array<string, Emissions>                                             $emissionsBySource
     * @param array<string, Emissions>                                             $emissionsByScope
     * @param array<string, array<string, Emissions>>                              $emissionsByScopeAndSource
     * @param list<array{propertyId: ?string, name: string, emissions: Emissions}> $emissionsByProperty
     * @param list<OrganizationalCarbonFootprintReportActivityLine>                $activityLines
     * @param list<OrganizationalCarbonFootprintReportExclusionLine>               $exclusionLines
     * @param list<DerivedSourceLine>                                              $derivedLines
     * @param list<NonConsolidatedPropertyLine>                                    $nonConsolidatedPropertyLines
     * @param list<OrganizationalCarbonFootprintReportBoundaryLine>                $boundaryLines
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
        public readonly Emissions $locationBasedTotalEmissions,
        public readonly int $electricityActivitiesWithoutLocationBased,
        public readonly array $emissionsBySource,
        public readonly array $emissionsByScope,
        public readonly array $emissionsByScopeAndSource,
        public readonly array $emissionsByProperty,
        public readonly array $activityLines,
        public readonly array $exclusionLines = [],
        public readonly ?OrganizationalCarbonFootprintReportMitigationPlan $mitigationPlan = null,
        public readonly bool $scope3Excluded = false,
        public readonly ?ExclusionReason $scope3ExclusionReason = null,
        public readonly ?string $scope3ExclusionComment = null,
        public readonly array $derivedLines = [],
        public readonly array $nonConsolidatedPropertyLines = [],
        public readonly bool $precalculated = false,
        public readonly float $gwpCh4 = 0.0,
        public readonly float $gwpN2O = 0.0,
        public readonly ?OperatingRegime $operatingRegime = null,
        public readonly array $boundaryLines = [],
        public readonly ?OrganizationalCarbonFootprintReportEmployeeSurvey $employeeSurvey = null,
        public readonly ?OrganizationalCarbonFootprintReportMaterialityAnnex $materialityAnnex = null,
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

    /** @return list<array{0: Scope, 1: Source, 2: array<string, float>}> */
    public function categoryMethodEmissions(): array
    {
        $emissions = [];

        foreach ($this->activityLines as $line) {
            $emissions[$line->scope->value][$line->source->value][$line->method->value] = ($emissions[$line->scope->value][$line->source->value][$line->method->value] ?? 0.0) + (float) $line->emissionsCo2eq;
        }

        foreach ($this->derivedLines as $line) {
            $method = CalculationMethod::DerivedWellToTank->value;
            $emissions[Scope::Scope3->value][$line->source->value][$method] = ($emissions[Scope::Scope3->value][$line->source->value][$method] ?? 0.0) + (float) $line->emissions->co2eq();
        }

        foreach ($this->nonConsolidatedPropertyLines as $line) {
            $method = CalculationMethod::forNonConsolidatedPropertyLine($line);

            if (null === $method) {
                continue;
            }

            $emissions[Scope::Scope3->value][$line->source->value][$method->value] = ($emissions[Scope::Scope3->value][$line->source->value][$method->value] ?? 0.0) + (float) $line->emissions->co2eq();
        }

        $categories = [];

        foreach (Scope::cases() as $scope) {
            foreach (Source::cases() as $source) {
                if (isset($emissions[$scope->value][$source->value])) {
                    $categories[] = [$scope, $source, $emissions[$scope->value][$source->value]];
                }
            }
        }

        return $categories;
    }

    /** @return list<array{source: ?string, method: ?CalculationMethod, years: list<int>, lines: int, emissions: float}> */
    public function emissionFactorSourceSummary(): array
    {
        $summary = [];

        foreach ($this->activityLines as $line) {
            $key = $line->factorSource ?? '';
            $summary[$key] ??= ['source' => $line->factorSource, 'method' => null, 'years' => [], 'lines' => 0, 'emissions' => 0.0];
            $summary[$key]['years'][$line->factorYear] = $line->factorYear;
            ++$summary[$key]['lines'];
            $summary[$key]['emissions'] += (float) $line->emissionsCo2eq;
        }

        $rows = [];

        foreach ($summary as $row) {
            $years = array_values($row['years']);
            sort($years);
            $rows[] = ['source' => $row['source'], 'method' => null, 'years' => $years, 'lines' => $row['lines'], 'emissions' => $row['emissions']];
        }

        if ([] !== $this->derivedLines) {
            $rows[] = [
                'source' => null,
                'method' => CalculationMethod::DerivedWellToTank,
                'years' => [],
                'lines' => count($this->derivedLines),
                'emissions' => array_sum(array_map(static fn(DerivedSourceLine $line): float => (float) $line->emissions->co2eq(), $this->derivedLines)),
            ];
        }

        foreach ([CalculationMethod::OperatorFootprint, CalculationMethod::OperatorContribution, CalculationMethod::ManualValue] as $method) {
            $lines = array_values(array_filter($this->nonConsolidatedPropertyLines, static fn(NonConsolidatedPropertyLine $line): bool => CalculationMethod::forNonConsolidatedPropertyLine($line) === $method));

            if ([] === $lines) {
                continue;
            }

            $rows[] = [
                'source' => null,
                'method' => $method,
                'years' => [],
                'lines' => count($lines),
                'emissions' => array_sum(array_map(static fn(NonConsolidatedPropertyLine $line): float => (float) $line->emissions->co2eq(), $lines)),
            ];
        }

        return $rows;
    }

    public function hasMaterialityDecisions(): bool
    {
        return true === $this->materialityAnnex?->hasDecisions;
    }

    public function boundaryTreatment(OrganizationalCarbonFootprintReportBoundaryLine $line): ?Source
    {
        $source = $line->scope3Source;

        if ($line->consolidated || null === $source || $this->scope3Excluded) {
            return null;
        }

        foreach ($this->exclusionLines as $exclusionLine) {
            if (Scope::Scope3 === $exclusionLine->scope && $exclusionLine->source === $source) {
                return null;
            }
        }

        return $source;
    }
}
