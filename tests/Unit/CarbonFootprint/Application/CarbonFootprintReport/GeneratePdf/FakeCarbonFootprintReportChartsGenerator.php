<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportCharts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportChartsGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\Shared\Domain\Model\Locale\Locale;

class FakeCarbonFootprintReportChartsGenerator implements CarbonFootprintReportChartsGenerator
{
    public function generate(CarbonFootprintReportData $data, Locale $locale): CarbonFootprintReportCharts
    {
        return new CarbonFootprintReportCharts(['bySource' => 'png-source']);
    }
}
