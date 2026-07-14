<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprintReport\Generate;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCharts;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTexts;
use App\Shared\Domain\Model\Locale\Locale;

class FakeReportPdfGenerator implements EventCarbonFootprintReportPdfGenerator
{
    public function generate(
        EventCarbonFootprintReportData $data,
        EventCarbonFootprintReportTexts $texts,
        EventCarbonFootprintReportCharts $charts,
        Locale $locale,
    ): string {
        $path = tempnam(sys_get_temp_dir(), 'ecf-report-test-');
        assert(false !== $path);

        file_put_contents($path, 'pdf-content-' . $locale->value);

        return $path;
    }
}
