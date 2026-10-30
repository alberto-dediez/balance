<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;

class InMemoryPropertyCarbonFootprintCertificateRepositoryTest extends PropertyCarbonFootprintCertificateRepositoryTest
{
    protected function createSUT(): PropertyCarbonFootprintCertificateRepository
    {
        return PropertyCarbonFootprintCertificateRepositoryMother::inMemory();
    }
}
