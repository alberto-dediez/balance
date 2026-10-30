<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Verify;

final class VerifyPropertyCarbonFootprintCertificateRequest
{
    public function __construct(
        public readonly string $verificationToken,
    ) {}
}
