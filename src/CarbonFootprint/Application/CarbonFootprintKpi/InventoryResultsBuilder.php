<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\OrganizationalCarbonFootprintReportLinesBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CalculationMethod;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\InventoryLine;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventoryResultsBuilder
{
    public function __construct(
        private EmissionsBreakdownBuilder $breakdownBuilder,
        private OrganizationalCarbonFootprintReportLinesBuilder $linesBuilder,
        private DerivedSourceEmissionsCalculator $derivedSourceEmissionsCalculator,
        private GasBreakdownCalculator $gasBreakdownCalculator,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private CompanyProperties $companyProperties,
        private TranslatorInterface $translator,
    ) {}

    public function build(PropertyCarbonFootprint|CompanyCarbonFootprint $footprint): InventoryResults
    {
        $breakdown = $this->breakdownBuilder->build($footprint, Scope::cases());

        $propertyNamesById = [];
        if ($footprint instanceof CompanyCarbonFootprint) {
            foreach ($this->companyProperties->all($footprint->companyId()) as $property) {
                $propertyNamesById[(string) $property->id()] = $property->name();
            }
        }

        $byScope = [];
        $byScopeAndSource = [];
        foreach (Scope::cases() as $scope) {
            $byScope[$scope->value] = (float) ($breakdown->byStage[$scope->value] ?? new Emissions())->co2eq();
            foreach ($scope->allSources() as $source) {
                $kg = (float) ($breakdown->byStageAndSource[$scope->value][$source->value] ?? new Emissions())->co2eq();

                if (0.0 !== $kg) {
                    $byScopeAndSource[$scope->value][$source->value] = $kg;
                }
            }
        }

        $emissionFactorGroupId = $footprint->emissionFactorGroupId();
        $gwp = (null !== $emissionFactorGroupId ? $this->emissionFactorGroupRepository->find($emissionFactorGroupId)?->gwp() : null) ?? new Gwp();

        $electricityWithLocationBased = $breakdown->electricityActivities - $breakdown->electricityActivitiesWithoutLocationBased;

        return new InventoryResults(
            totalKg: (float) $footprint->totalEmissions()->co2eq(),
            recalculatedTotalKg: (float) $breakdown->total->co2eq(),
            byScope: $byScope,
            byScopeAndSource: $byScopeAndSource,
            linesByScopeAndSource: $this->linesByScopeAndSource($footprint, $propertyNamesById),
            contribution: $this->contribution($byScopeAndSource, (float) $breakdown->total->co2eq()),
            gas: $this->gasBreakdownCalculator->calculate($footprint, $gwp, $breakdown->total),
            dualReportingActive: $electricityWithLocationBased > 0 && !$footprint->isPrecalculated(),
            scope2MarketKg: $byScope[Scope::Scope2->value],
            scope2LocationKg: (float) $breakdown->locationBasedElectricity->co2eq(),
            locationBasedTotalKg: (float) $breakdown->locationBasedTotal->co2eq(),
            electricityActivitiesWithoutLocationBased: $breakdown->electricityActivitiesWithoutLocationBased,
            scope3Excluded: $footprint->isStageExcluded(Scope::Scope3),
        );
    }

    /**
     * @param array<string, string> $propertyNamesById
     *
     * @return array<string, array<string, list<InventoryLine>>>
     */
    private function linesByScopeAndSource(PropertyCarbonFootprint|CompanyCarbonFootprint $footprint, array $propertyNamesById): array
    {
        $lines = [];

        foreach ($this->linesBuilder->activityLines($footprint, $propertyNamesById) as $line) {
            $lines[] = new InventoryLine(
                scope: $line->scope,
                source: $line->source,
                itemName: null !== $line->propertyName ? sprintf('%s · %s', $line->propertyName, $line->itemName) : $line->itemName,
                unit: $line->unit,
                quantity: $line->quantity,
                factorCo2eq: (float) $line->factorCo2eq,
                emissionsCo2eq: (float) $line->emissionsCo2eq,
                method: $line->method,
            );
        }

        foreach ($this->derivedSourceEmissionsCalculator->lines($footprint, Scope::Scope3) as $line) {
            $itemName = sprintf(
                '%s · %s',
                $this->translator->trans($line->origin->source->value, [], 'source'),
                $this->translator->trans($line->activityType->value, [], 'activityType'),
            );
            $propertyName = null !== $line->origin->propertyId ? ($propertyNamesById[(string) $line->origin->propertyId] ?? null) : null;

            $lines[] = new InventoryLine(
                scope: Scope::Scope3,
                source: $line->source,
                itemName: null !== $propertyName ? sprintf('%s · %s', $propertyName, $itemName) : $itemName,
                unit: null,
                quantity: null,
                factorCo2eq: null,
                emissionsCo2eq: (float) $line->emissions->co2eq(),
                method: CalculationMethod::DerivedWellToTank,
            );
        }

        foreach ($this->derivedSourceEmissionsCalculator->nonConsolidatedPropertyLines($footprint, Scope::Scope3) as $line) {
            $lines[] = new InventoryLine(
                scope: Scope::Scope3,
                source: $line->source,
                itemName: $propertyNamesById[(string) $line->propertyId] ?? (string) $line->propertyId,
                unit: null,
                quantity: null,
                factorCo2eq: null,
                emissionsCo2eq: (float) $line->emissions->co2eq(),
                method: CalculationMethod::forNonConsolidatedPropertyLine($line),
            );
        }

        usort($lines, static fn(InventoryLine $a, InventoryLine $b): int => $b->emissionsCo2eq <=> $a->emissionsCo2eq);

        $grouped = [];
        foreach ($lines as $line) {
            $grouped[$line->scope->value][$line->source->value][] = $line;
        }

        return $grouped;
    }

    /**
     * @param array<string, array<string, float>> $byScopeAndSource
     *
     * @return list<array{scope: Scope, source: Source, kg: float, percent: float, cumulativePercent: float}>
     */
    private function contribution(array $byScopeAndSource, float $totalKg): array
    {
        $rows = [];
        foreach ($byScopeAndSource as $scopeValue => $bySource) {
            foreach ($bySource as $sourceValue => $kg) {
                $rows[] = ['scope' => Scope::from($scopeValue), 'source' => Source::from($sourceValue), 'kg' => $kg];
            }
        }

        usort($rows, static fn(array $a, array $b): int => $b['kg'] <=> $a['kg']);

        $cumulative = 0.0;
        $contribution = [];
        foreach ($rows as $row) {
            $percent = 0.0 !== $totalKg ? $row['kg'] / $totalKg * 100 : 0.0;
            $cumulative += $percent;
            $contribution[] = [
                'scope' => $row['scope'],
                'source' => $row['source'],
                'kg' => $row['kg'],
                'percent' => $percent,
                'cumulativePercent' => $cumulative,
            ];
        }

        return $contribution;
    }
}
