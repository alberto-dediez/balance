<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Generate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateVerificationUrlGenerator;
use App\Shared\Domain\Model\Locale\Locale;

class FakePropertyCarbonFootprintCertificateVerificationUrlGenerator implements PropertyCarbonFootprintCertificateVerificationUrlGenerator
{
    public function generate(string $verificationToken, Locale $locale): string
    {
        return sprintf('https://example.com/%s/footprint-certificate/%s', $locale->value, $verificationToken);
    }
}
