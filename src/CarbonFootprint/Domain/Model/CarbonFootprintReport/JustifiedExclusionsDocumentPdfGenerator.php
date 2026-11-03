<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;

interface JustifiedExclusionsDocumentPdfGenerator
{
    public function generate(OrganizationalCarbonFootprintReportData $data, Locale $locale, PdfBranding $branding): string;
}
