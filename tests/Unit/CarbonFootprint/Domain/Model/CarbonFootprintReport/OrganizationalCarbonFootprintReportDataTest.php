<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\DerivedSourceLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CalculationMethod;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportActivityLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportBoundaryLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;
use PHPUnit\Framework\TestCase;

final class OrganizationalCarbonFootprintReportDataTest extends TestCase
{
    public function testTheCategoryMethodEmissionsSplitEachCategoryByMethod(): void
    {
        $methods = $this->methodsOf(OrganizationalCarbonFootprintReportDataMother::withMixedMethods(), Scope::Scope3, Source::PurchasedGoodsAndServices);

        $this->assertSame([CalculationMethod::SpendBased->value => 3000.0, CalculationMethod::SupplierSpecific->value => 1200.0], $methods);
        $this->assertSame([CalculationMethod::DerivedWellToTank->value => 590.0], $this->methodsOf(OrganizationalCarbonFootprintReportDataMother::withMixedMethods(), Scope::Scope3, Source::FuelAndEnergyRelated));
        $this->assertSame([CalculationMethod::DistanceBased->value => 2000.0], $this->methodsOf(OrganizationalCarbonFootprintReportDataMother::withMixedMethods(), Scope::Scope3, Source::UpstreamTransport));
        $this->assertSame([CalculationMethod::WasteTypeSpecific->value => 500.0], $this->methodsOf(OrganizationalCarbonFootprintReportDataMother::withMixedMethods(), Scope::Scope3, Source::OperationsWaste));
    }

    public function testTheCategoryMethodEmissionsOfTheNonConsolidatedPropertiesFollowTheirOrigin(): void
    {
        $this->assertSame([CalculationMethod::OperatorFootprint->value => 5000.0], $this->methodsOf(OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties(), Scope::Scope3, Source::Franchises));
        $this->assertSame([CalculationMethod::OperatorContribution->value => 5000.0], $this->methodsOf(OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties(true), Scope::Scope3, Source::Franchises));
        $this->assertSame([CalculationMethod::ManualValue->value => 4500.0], $this->methodsOf(OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties(), Scope::Scope3, Source::DownstreamLeasedAssets));
    }

    public function testTheEmissionFactorSourceSummaryCoversTheWholeInventoryIncludingTheDerivedEmissions(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::withMixedMethods();

        $summary = $data->emissionFactorSourceSummary();
        $derived = array_values(array_filter($summary, static fn(array $row): bool => CalculationMethod::DerivedWellToTank === $row['method']));

        $this->assertCount(1, $derived);
        $this->assertSame(['source' => 'DEFRA', 'method' => CalculationMethod::DerivedWellToTank, 'years' => [2024, 2025], 'lines' => 2, 'emissions' => 590.0], $derived[0]);
        $this->assertContains(['source' => 'MITECO', 'method' => null, 'years' => [2025], 'lines' => 2, 'emissions' => 1430.0], $summary);
        $this->assertEqualsWithDelta((float) $data->totalEmissions->co2eq(), array_sum(array_column($summary, 'emissions')), 0.001);
    }

    public function testTheEmissionFactorSourceSummaryHasARowPerMethodOfTheNonConsolidatedProperties(): void
    {
        $summary = OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines()->emissionFactorSourceSummary();

        $this->assertContains(['source' => null, 'method' => CalculationMethod::OperatorFootprint, 'years' => [], 'lines' => 1, 'emissions' => 5000.0], $summary);
        $this->assertContains(['source' => null, 'method' => CalculationMethod::ManualValue, 'years' => [], 'lines' => 1, 'emissions' => 4500.0], $summary);
        $this->assertSame([], array_values(array_filter($summary, static fn(array $row): bool => CalculationMethod::OperatorContribution === $row['method'])));
    }

    public function testTheEmissionFactorSourceSummaryGroupsTheWellToTankLinesBySourceAndYear(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::withScope3();
        $derivedLines = [
            ...$data->derivedLines,
            new DerivedSourceLine(Source::FuelAndEnergyRelated, new ActivityPlacement(Scope::Scope1, Source::OwnVehicles), ActivityType::MachineryFuel, new Emissions('50', '50', '0', '0'), ActivityId::generate(), 20.0, Unit::Liter, '2.5', Unit::Liter, 2023, null),
        ];

        $summary = (new OrganizationalCarbonFootprintReportData(...array_merge(get_object_vars($data), ['derivedLines' => $derivedLines])))->emissionFactorSourceSummary();
        $derived = array_values(array_filter($summary, static fn(array $row): bool => CalculationMethod::DerivedWellToTank === $row['method']));

        $this->assertSame([
            ['source' => 'DEFRA', 'method' => CalculationMethod::DerivedWellToTank, 'years' => [2024, 2025], 'lines' => 2, 'emissions' => 590.0],
            ['source' => null, 'method' => CalculationMethod::DerivedWellToTank, 'years' => [2023], 'lines' => 1, 'emissions' => 50.0],
        ], $derived);
    }

    public function testItDetectsTheActivitiesAccountedOnlyInCo2eq(): void
    {
        $refrigerant = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope1, Source::RefrigerantLeaks, null, 'R-410A', ActivityType::RefrigerantGas, CalculationMethod::ActivityBased, 2.0, Unit::Kilogram, '2088', Unit::Kilogram, 2025, 'MITECO', '4176');
        $reported = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope1, Source::FixedFacilities, null, 'Caldera comunicada', ActivityType::ReportedEmissions, CalculationMethod::ReportedByOrganization, 180.0, Unit::Kilogram, '1', Unit::Kilogram, 2025, 'reported', '180');
        $data = OrganizationalCarbonFootprintReportDataMother::forProperty();

        $this->assertTrue(OrganizationalCarbonFootprintReportDataMother::withActivityLines($data, [...$data->activityLines, $refrigerant])->hasCo2eqOnlyActivities());
        $this->assertTrue(OrganizationalCarbonFootprintReportDataMother::withActivityLines($data, [...$data->activityLines, $reported])->hasCo2eqOnlyActivities());
        $this->assertFalse($data->hasCo2eqOnlyActivities());
    }

    public function testTheBaseYearVariationKeepsItsSign(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::withScope3();

        $this->assertEqualsWithDelta(17.75, OrganizationalCarbonFootprintReportDataMother::withBaseYear($data, OrganizationalCarbonFootprintReportDataMother::baseYear(baseYearTotalKgCo2eq: '8000'))->baseYearVariationPercent(), 0.001);
        $this->assertEqualsWithDelta(-5.8, OrganizationalCarbonFootprintReportDataMother::withBaseYear($data, OrganizationalCarbonFootprintReportDataMother::baseYear(baseYearTotalKgCo2eq: '10000'))->baseYearVariationPercent(), 0.001);
    }

    public function testThereIsNoBaseYearVariationWithoutDeclarationWithAZeroSnapshotOrInTheBaseYearItself(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::withScope3();

        $this->assertNull($data->baseYearVariationPercent());
        $this->assertFalse($data->isBaseYear());
        $this->assertNull(OrganizationalCarbonFootprintReportDataMother::withBaseYear($data, OrganizationalCarbonFootprintReportDataMother::baseYear(baseYearTotalKgCo2eq: '0'))->baseYearVariationPercent());

        $baseYearData = OrganizationalCarbonFootprintReportDataMother::withBaseYear($data, OrganizationalCarbonFootprintReportDataMother::baseYear(year: 2025));
        $this->assertTrue($baseYearData->isBaseYear());
        $this->assertNull($baseYearData->baseYearVariationPercent());
        $this->assertFalse($baseYearData->baseYearExceedsThreshold());
    }

    public function testTheBaseYearThresholdIsExceededOnlyAboveIt(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::withScope3();

        $this->assertTrue(OrganizationalCarbonFootprintReportDataMother::withBaseYear($data, OrganizationalCarbonFootprintReportDataMother::baseYear(baseYearTotalKgCo2eq: '10000', recalculationThresholdPercent: '5.7'))->baseYearExceedsThreshold());
        $this->assertFalse(OrganizationalCarbonFootprintReportDataMother::withBaseYear($data, OrganizationalCarbonFootprintReportDataMother::baseYear(baseYearTotalKgCo2eq: '10000', recalculationThresholdPercent: '5.9'))->baseYearExceedsThreshold());
    }

    public function testAZeroBaseYearSnapshotExceedsTheThresholdWhenTheFootprintHasEmissions(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::withScope3();
        $withZeroSnapshot = OrganizationalCarbonFootprintReportDataMother::withBaseYear($data, OrganizationalCarbonFootprintReportDataMother::baseYear(baseYearTotalKgCo2eq: '0'));

        $this->assertGreaterThan(0.0, (float) $data->totalEmissions->co2eq());
        $this->assertNull($withZeroSnapshot->baseYearVariationPercent());
        $this->assertTrue($withZeroSnapshot->baseYearExceedsThreshold());
        $this->assertFalse(OrganizationalCarbonFootprintReportDataMother::withBaseYear($data, OrganizationalCarbonFootprintReportDataMother::baseYear(year: 2025, baseYearTotalKgCo2eq: '0'))->baseYearExceedsThreshold());
    }

    public function testTheSupplierDataShareCountsOnlyTheAnsweredContributions(): void
    {
        $this->assertEqualsWithDelta(20.0, OrganizationalCarbonFootprintReportDataMother::withProviderLines()->supplierDataSharePercent(), 0.001);
    }

    public function testThereIsNoSupplierDataShareWithoutScope3Emissions(): void
    {
        $this->assertNull(OrganizationalCarbonFootprintReportDataMother::withProviderLines(OrganizationalCarbonFootprintReportDataMother::forProperty())->supplierDataSharePercent());
        $this->assertNull(OrganizationalCarbonFootprintReportDataMother::withProviderLines(OrganizationalCarbonFootprintReportDataMother::withScope3Excluded())->supplierDataSharePercent());
    }

    public function testTheSourceLinesCoverActivityDerivedAndNonConsolidatedLines(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties();

        $this->assertTrue($data->hasSourceLines(Scope::Scope1, Source::FixedFacilities));
        $this->assertFalse($data->hasSourceLines(Scope::Scope1, Source::RefrigerantLeaks));
        $this->assertTrue($data->hasSourceLines(Scope::Scope3, Source::FuelAndEnergyRelated));
        $this->assertTrue($data->hasSourceLines(Scope::Scope3, Source::Franchises));
        $this->assertFalse($data->hasSourceLines(Scope::Scope3, Source::CapitalGoods));
    }

    public function testTheBoundaryTreatmentGivesTheScope3CategoryOfANonConsolidatedProperty(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines();

        $this->assertSame(Source::Franchises, $data->boundaryTreatment($this->boundaryLine($data, 'Hotel Franquicia')));
        $this->assertSame(Source::DownstreamLeasedAssets, $data->boundaryTreatment($this->boundaryLine($data, 'Hotel Arrendado')));
        $this->assertNull($data->boundaryTreatment($this->boundaryLine($data, 'Hotel Sol')));
    }

    public function testTheBoundaryTreatmentLeavesOutAPropertyWhoseCategoryIsExcluded(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines(Source::Franchises);

        $this->assertNull($data->boundaryTreatment($this->boundaryLine($data, 'Hotel Franquicia')));
        $this->assertSame(Source::DownstreamLeasedAssets, $data->boundaryTreatment($this->boundaryLine($data, 'Hotel Arrendado')));
    }

    public function testTheBoundaryTreatmentLeavesOutEveryNonConsolidatedPropertyWhenTheScope3IsExcluded(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines(scope3Excluded: true);

        $this->assertNull($data->boundaryTreatment($this->boundaryLine($data, 'Hotel Franquicia')));
        $this->assertNull($data->boundaryTreatment($this->boundaryLine($data, 'Hotel Arrendado')));
    }

    /** @return array<string, float> */
    private function methodsOf(OrganizationalCarbonFootprintReportData $data, Scope $scope, Source $source): array
    {
        foreach ($data->categoryMethodEmissions() as [$categoryScope, $categorySource, $methods]) {
            if ($categoryScope === $scope && $categorySource === $source) {
                return $methods;
            }
        }

        return [];
    }

    private function boundaryLine(OrganizationalCarbonFootprintReportData $data, string $propertyName): OrganizationalCarbonFootprintReportBoundaryLine
    {
        foreach ($data->boundaryLines as $line) {
            if ($line->propertyName === $propertyName) {
                return $line;
            }
        }

        $this->fail(sprintf('Boundary line %s not found', $propertyName));
    }
}
