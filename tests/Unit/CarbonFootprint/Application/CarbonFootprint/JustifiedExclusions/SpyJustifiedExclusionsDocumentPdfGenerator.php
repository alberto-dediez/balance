<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\JustifiedExclusionsDocumentPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;

class SpyJustifiedExclusionsDocumentPdfGenerator implements JustifiedExclusionsDocumentPdfGenerator
{
    public ?OrganizationalCarbonFootprintReportData $data = null;
    public ?Locale $locale = null;
    public ?PdfBranding $branding = null;

    public function generate(OrganizationalCarbonFootprintReportData $data, Locale $locale, PdfBranding $branding): string
    {
        $this->data = $data;
        $this->locale = $locale;
        $this->branding = $branding;

        return '/tmp/justified-exclusions.pdf';
    }
}
