<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\Shared\Domain\Model\Locale\Locale;

interface EventCarbonFootprintReportPdfGenerator
{
    public function generate(
        EventCarbonFootprintReportData $data,
        EventCarbonFootprintReportTexts $texts,
        EventCarbonFootprintReportCharts $charts,
        Locale $locale,
    ): string;
}
