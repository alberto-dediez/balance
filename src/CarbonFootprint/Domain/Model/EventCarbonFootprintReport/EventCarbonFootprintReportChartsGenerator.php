<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\Shared\Domain\Model\Locale\Locale;

interface EventCarbonFootprintReportChartsGenerator
{
    public function generate(EventCarbonFootprintReportData $data, Locale $locale): EventCarbonFootprintReportCharts;
}
