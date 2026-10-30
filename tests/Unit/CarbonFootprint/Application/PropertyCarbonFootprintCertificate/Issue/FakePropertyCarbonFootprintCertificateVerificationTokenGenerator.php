<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateVerificationTokenGenerator;

class FakePropertyCarbonFootprintCertificateVerificationTokenGenerator implements PropertyCarbonFootprintCertificateVerificationTokenGenerator
{
    private int $issued = 0;

    public function generate(): string
    {
        ++$this->issued;

        return sprintf('footprint-verification-token-%04d', $this->issued);
    }
}
