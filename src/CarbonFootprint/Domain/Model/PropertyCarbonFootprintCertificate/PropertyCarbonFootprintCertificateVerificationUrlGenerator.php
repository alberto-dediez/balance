<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\Shared\Domain\Model\Locale\Locale;

interface PropertyCarbonFootprintCertificateVerificationUrlGenerator
{
    public function generate(string $verificationToken, Locale $locale): string;
}
