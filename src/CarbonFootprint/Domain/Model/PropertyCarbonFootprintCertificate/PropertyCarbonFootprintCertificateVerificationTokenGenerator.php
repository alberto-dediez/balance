<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

interface PropertyCarbonFootprintCertificateVerificationTokenGenerator
{
    public function generate(): string;
}
