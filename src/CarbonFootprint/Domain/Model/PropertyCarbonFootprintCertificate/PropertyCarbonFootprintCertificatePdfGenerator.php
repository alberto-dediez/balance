<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;

interface PropertyCarbonFootprintCertificatePdfGenerator
{
    public function generate(PropertyCarbonFootprintCertificateData $data, Locale $locale, PdfBranding $branding): string;
}
