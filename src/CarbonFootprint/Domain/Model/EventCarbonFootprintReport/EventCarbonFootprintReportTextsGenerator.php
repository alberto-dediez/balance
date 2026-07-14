<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\Shared\Domain\Model\Locale\Locale;

interface EventCarbonFootprintReportTextsGenerator
{
    public function generate(EventCarbonFootprintReportData $data, Locale $locale): EventCarbonFootprintReportTexts;

    public function translate(EventCarbonFootprintReportTexts $texts, Locale $from, Locale $to): EventCarbonFootprintReportTexts;
}
