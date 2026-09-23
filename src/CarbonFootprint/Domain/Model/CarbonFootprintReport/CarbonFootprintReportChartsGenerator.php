<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\Shared\Domain\Model\Locale\Locale;

interface CarbonFootprintReportChartsGenerator
{
    public function generate(CarbonFootprintReportData $data, Locale $locale): CarbonFootprintReportCharts;
}
