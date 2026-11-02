<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintKpi\View;

use App\CarbonFootprint\Application\CarbonFootprintKpi\GetDashboard\GetCarbonFootprintKpiDashboardResponse;
use App\CarbonFootprint\Application\CarbonFootprintKpi\InventoryResults;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubject;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\GasBreakdown;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\InterannualVariation;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintKpi\View\KpiDashboardView;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

final class KpiDashboardViewTest extends TestCase
{
    public function testEachVariationChartHasItsOwnCategories(): void
    {
        $view = (new KpiDashboardView(new FakeTranslator()))->build(new GetCarbonFootprintKpiDashboardResponse(
            subject: KpiSubject::forAccount(CarbonFootprintReportSubjectType::Property, null),
            hasData: true,
            selectedYear: 2025,
            inventory: new InventoryResults(
                totalKg: 96000.0,
                recalculatedTotalKg: 96000.0,
                byScope: [],
                byScopeAndSource: [],
                linesByScopeAndSource: [],
                contribution: [],
                gas: GasBreakdown::fromComponents(96000.0, 0.0, 0.0, 0.0, 96000.0),
                dualReportingActive: false,
                scope2MarketKg: 0.0,
                scope2LocationKg: 0.0,
                locationBasedTotalKg: 96000.0,
                electricityActivitiesWithoutLocationBased: 0,
                scope3Excluded: false,
            ),
            variationVsPrevious: new InterannualVariation(2024, 2025, 100000.0, 96000.0, -4000.0, 10000.0, -960.0, -13040.0, IntensityIndicator::PerStay),
            variationVsBaseYear: new InterannualVariation(2020, 2025, 90000.0, 96000.0, 6000.0, null, 2000.0, 4000.0, null),
        ));

        $this->assertSame([
            [
                'title' => 'vs. 2024',
                'categories' => ['Efecto actividad', 'Efecto eficiencia', 'Efecto factores'],
                'series' => [['name' => 'vs. 2024', 'data' => [10.0, -13.04, -0.96]]],
            ],
            [
                'title' => 'vs. año base (2020)',
                'categories' => ['Actividad y eficiencia', 'Efecto factores'],
                'series' => [['name' => 'vs. año base (2020)', 'data' => [4.0, 2.0]]],
            ],
        ], $view['variationCharts']);
    }
}
