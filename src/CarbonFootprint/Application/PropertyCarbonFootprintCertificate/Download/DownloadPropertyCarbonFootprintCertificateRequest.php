<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Download;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\Shared\Domain\Model\Locale\Locale;

final class DownloadPropertyCarbonFootprintCertificateRequest
{
    public function __construct(
        public readonly PropertyCarbonFootprintCertificateId $propertyCarbonFootprintCertificateId,
        public readonly ?Locale $locale = null,
    ) {}
}
