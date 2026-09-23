<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportCharts;

class CarbonFootprintReportChartsMother
{
    public static function withFixturePng(): CarbonFootprintReportCharts
    {
        $png = (string) file_get_contents(__DIR__ . '/fixtures/chart.png');

        return new CarbonFootprintReportCharts([
            'bySource' => $png,
            'byPhase' => $png,
            'byPhaseAndSource' => $png,
            'compensation' => $png,
            'byScope' => $png,
            'byProperty' => $png,
        ]);
    }
}
