<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\Shared\Domain\Model\Locale\Locale;

interface CarbonFootprintReportPdfGenerator
{
    public function generate(
        CarbonFootprintReportData $data,
        CarbonFootprintReportTexts $texts,
        CarbonFootprintReportCharts $charts,
        Locale $locale,
        string $themeSlug,
    ): string;
}
