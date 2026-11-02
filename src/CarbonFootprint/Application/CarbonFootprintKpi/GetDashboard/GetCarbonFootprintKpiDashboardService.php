<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi\GetDashboard;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CarbonFootprintKpi\CarbonFootprintKpiAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintKpi\InventoryResults;
use App\CarbonFootprint\Application\CarbonFootprintKpi\InventoryResultsBuilder;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubject;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubjectResolver;
use App\CarbonFootprint\Application\CarbonFootprintKpi\PropertyComparisonRow;
use App\CarbonFootprint\Application\CarbonFootprintKpi\SubjectFootprints;
use App\CarbonFootprint\Application\CarbonFootprintKpi\TimeSeriesPoint;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclaration;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRepository;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicatorsCalculator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicatorValue;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\InterannualVariation;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\InterannualVariationDecomposer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmark;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\Property;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class GetCarbonFootprintKpiDashboardService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private KpiSubjectResolver $subjectResolver,
        private SubjectFootprints $subjectFootprints,
        private InventoryResultsBuilder $inventoryResultsBuilder,
        private EmissionsBreakdownBuilder $breakdownBuilder,
        private IntensityIndicatorsCalculator $intensityIndicatorsCalculator,
        private InterannualVariationDecomposer $variationDecomposer,
        private BaseYearDeclarationRepository $baseYearDeclarationRepository,
        private SectorBenchmarkRepository $sectorBenchmarkRepository,
        private CompanyProperties $companyProperties,
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private CarbonFootprintKpiAuthorizer $authorizer,
    ) {}

    public function __invoke(GetCarbonFootprintKpiDashboardRequest $request, User $user): GetCarbonFootprintKpiDashboardResponse
    {
        $subject = $this->subjectResolver->resolve($request->subjectType, $request->subjectId);

        if (null === $subject) {
            throw new \InvalidArgumentException('Carbon footprint KPI subject not found');
        }

        $this->authorizer->denyUnlessCanDoThat($subject, Action::View, $user);

        $footprints = $this->subjectFootprints->completedOf($subject);

        if ([] === $footprints) {
            return new GetCarbonFootprintKpiDashboardResponse(subject: $subject, hasData: false);
        }

        $footprintsByYear = [];
        foreach ($footprints as $footprint) {
            $footprintsByYear[$footprint->year()] = $footprint;
        }

        $availableYears = array_keys($footprintsByYear);
        $selectedYear = null !== $request->year && isset($footprintsByYear[$request->year]) ? $request->year : max($availableYears);
        $selected = $footprintsByYear[$selectedYear];

        $inventory = $this->inventoryResultsBuilder->build($selected);
        $indicators = $this->intensityIndicatorsCalculator->calculate($inventory->totalKg, $selected->intensityDenominators());

        $baseYear = $this->baseYearDeclarationRepository->findBySubject($subject->type, $subject->id);
        $series = $this->series($footprints, $baseYear);

        $baseYearCurrentTotalKg = null;
        foreach ($series as $point) {
            if ($point->isBaseYear) {
                $baseYearCurrentTotalKg = $point->totalKg;
            }
        }

        $primaryIndicator = $baseYear?->primaryIndicator();
        $previous = $this->subjectFootprints->previousCompleted($subject, $selectedYear);
        $baseYearFootprint = null !== $baseYear ? ($footprintsByYear[$baseYear->year()] ?? null) : null;

        $variationVsBaseYear = null;
        if (null !== $baseYearFootprint && $baseYearFootprint->year() !== $selectedYear && $baseYearFootprint->year() !== $previous?->year()) {
            $variationVsBaseYear = $this->variation($baseYearFootprint, $selected, $inventory, $primaryIndicator);
        }

        $comparisonIndicator = null;
        $propertyComparison = [];
        $propertiesWithoutData = [];
        if ($subject->isCompany()) {
            $comparisonIndicator = $request->comparisonIndicator ?? $primaryIndicator ?? ($indicators[0] ?? null)?->indicator;
            [$propertyComparison, $propertiesWithoutData] = $this->propertyComparison($subject, $selectedYear, $comparisonIndicator);
        }

        return new GetCarbonFootprintKpiDashboardResponse(
            subject: $subject,
            hasData: true,
            availableYears: $availableYears,
            selectedYear: $selectedYear,
            inventory: $inventory,
            indicators: $indicators,
            benchmarks: $this->benchmarks($subject, $selectedYear, $indicators),
            series: $series,
            baseYear: $baseYear,
            baseYearAlert: null !== $baseYear && null !== $baseYearCurrentTotalKg && $baseYear->exceedsThreshold($baseYearCurrentTotalKg),
            baseYearCurrentTotalKg: $baseYearCurrentTotalKg,
            variationVsPrevious: null !== $previous ? $this->variation($previous, $selected, $inventory, $primaryIndicator) : null,
            variationVsBaseYear: $variationVsBaseYear,
            comparisonIndicator: $comparisonIndicator,
            propertyComparison: $propertyComparison,
            propertiesWithoutData: $propertiesWithoutData,
            footprintIdsByYear: array_map(static fn(PropertyCarbonFootprint|CompanyCarbonFootprint $footprint): string => (string) $footprint->id(), $footprintsByYear),
        );
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof GetCarbonFootprintKpiDashboardRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GetCarbonFootprintKpiDashboardRequest;
    }

    /**
     * @param list<PropertyCarbonFootprint|CompanyCarbonFootprint> $footprints
     *
     * @return list<TimeSeriesPoint>
     */
    private function series(array $footprints, ?BaseYearDeclaration $baseYear): array
    {
        $series = [];

        foreach ($footprints as $footprint) {
            $breakdown = $this->breakdownBuilder->build($footprint, Scope::cases());
            $totalKg = (float) $footprint->totalEmissions()->co2eq();

            $byScopeKg = [];
            foreach (Scope::cases() as $scope) {
                $byScopeKg[$scope->value] = (float) ($breakdown->byStage[$scope->value] ?? new Emissions())->co2eq();
            }

            $indicators = [];
            foreach ($this->intensityIndicatorsCalculator->calculate($totalKg, $footprint->intensityDenominators()) as $value) {
                $indicators[$value->indicator->value] = $value;
            }

            $series[] = new TimeSeriesPoint(
                year: $footprint->year(),
                totalKg: $totalKg,
                byScopeKg: $byScopeKg,
                indicators: $indicators,
                isBaseYear: null !== $baseYear && $baseYear->year() === $footprint->year(),
            );
        }

        return $series;
    }

    private function variation(
        PropertyCarbonFootprint|CompanyCarbonFootprint $from,
        PropertyCarbonFootprint|CompanyCarbonFootprint $to,
        InventoryResults $toInventory,
        ?IntensityIndicator $primaryIndicator,
    ): InterannualVariation {
        $fromInventory = $this->inventoryResultsBuilder->build($from);

        return $this->variationDecomposer->decompose(
            $from->year(),
            $fromInventory->totalKg,
            $fromInventory->lines(),
            $to->year(),
            $toInventory->totalKg,
            $toInventory->lines(),
            $primaryIndicator,
            null !== $primaryIndicator ? $from->intensityDenominators()->denominatorFor($primaryIndicator) : null,
            null !== $primaryIndicator ? $to->intensityDenominators()->denominatorFor($primaryIndicator) : null,
        );
    }

    /**
     * @param list<IntensityIndicatorValue> $indicators
     *
     * @return array<string, SectorBenchmark>
     */
    private function benchmarks(KpiSubject $subject, int $year, array $indicators): array
    {
        $benchmarks = [];

        foreach ($indicators as $value) {
            $benchmark = $this->sectorBenchmarkRepository->findBySegment($subject->type, $subject->segmentKey, $year, $value->indicator);

            if (null !== $benchmark && $benchmark->isPublishable()) {
                $benchmarks[$value->indicator->value] = $benchmark;
            }
        }

        return $benchmarks;
    }

    /** @return array{0: list<PropertyComparisonRow>, 1: list<array{property: Property, reason: string}>} */
    private function propertyComparison(KpiSubject $subject, int $year, ?IntensityIndicator $indicator): array
    {
        $rows = [];
        $withoutData = [];

        foreach ($this->companyProperties->all(new CompanyId($subject->id)) as $property) {
            $footprint = $this->propertyCarbonFootprintRepository->findByPropertyIdAndYear($property->id(), $year);

            if (null === $footprint || $footprint->isRemoved() || !$footprint->isCompleted()) {
                $withoutData[] = ['property' => $property, 'reason' => GetCarbonFootprintKpiDashboardResponse::NO_COMPLETED_FOOTPRINT];

                continue;
            }

            $denominator = null !== $indicator ? $footprint->intensityDenominators()->denominatorFor($indicator) : null;

            if (null === $denominator) {
                $withoutData[] = ['property' => $property, 'reason' => GetCarbonFootprintKpiDashboardResponse::NO_DENOMINATOR];

                continue;
            }

            $totalKg = (float) $footprint->totalEmissions()->co2eq();

            $rows[] = new PropertyComparisonRow(
                propertyId: (string) $property->id(),
                name: $property->name(),
                operatingRegime: $property->operatingRegime(),
                totalKg: $totalKg,
                denominator: $denominator,
                intensity: IntensityIndicatorsCalculator::intensity($totalKg, $denominator),
            );
        }

        usort($rows, static fn(PropertyComparisonRow $a, PropertyComparisonRow $b): int => $b->intensity <=> $a->intensity);

        return [$rows, $withoutData];
    }
}
