<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CalculationMethod;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportBoundaryLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use PHPUnit\Framework\TestCase;

final class OrganizationalCarbonFootprintReportDataTest extends TestCase
{
    public function testTheCategoryMethodEmissionsSplitEachCategoryByMethod(): void
    {
        $methods = $this->methodsOf(OrganizationalCarbonFootprintReportDataMother::withMixedMethods(), Scope::Scope3, Source::PurchasedGoodsAndServices);

        $this->assertSame([CalculationMethod::SpendBased->value => 3000.0, CalculationMethod::SupplierSpecific->value => 1200.0], $methods);
        $this->assertSame([CalculationMethod::DerivedWellToTank->value => 590.0], $this->methodsOf(OrganizationalCarbonFootprintReportDataMother::withMixedMethods(), Scope::Scope3, Source::FuelAndEnergyRelated));
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
        $this->assertSame(['source' => null, 'method' => CalculationMethod::DerivedWellToTank, 'years' => [], 'lines' => 2, 'emissions' => 590.0], $derived[0]);
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
