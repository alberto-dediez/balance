<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprintReport\Generate;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCharts;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportChartsGenerator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportData;
use App\Shared\Domain\Model\Locale\Locale;

class FakeReportChartsGenerator implements EventCarbonFootprintReportChartsGenerator
{
    public function generate(EventCarbonFootprintReportData $data, Locale $locale): EventCarbonFootprintReportCharts
    {
        return new EventCarbonFootprintReportCharts(
            bySource: 'png-source',
            byPhase: 'png-phase',
            byPhaseAndSource: 'png-phase-source',
        );
    }
}
