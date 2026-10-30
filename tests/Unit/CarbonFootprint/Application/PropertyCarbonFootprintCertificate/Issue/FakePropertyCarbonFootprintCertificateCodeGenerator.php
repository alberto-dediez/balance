<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateCodeGenerator;

class FakePropertyCarbonFootprintCertificateCodeGenerator implements PropertyCarbonFootprintCertificateCodeGenerator
{
    private int $issued = 0;

    public function generate(): string
    {
        ++$this->issued;

        return sprintf('HUEL-LAAA-%04d', $this->issued);
    }
}
