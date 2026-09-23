<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\Shared\Domain\Model\Locale\Locale;

interface CarbonFootprintReportTextsGenerator
{
    public function generate(CarbonFootprintReportData $data, Locale $locale): CarbonFootprintReportTexts;

    public function translate(CarbonFootprintReportTexts $texts, Locale $from, Locale $to): CarbonFootprintReportTexts;
}
