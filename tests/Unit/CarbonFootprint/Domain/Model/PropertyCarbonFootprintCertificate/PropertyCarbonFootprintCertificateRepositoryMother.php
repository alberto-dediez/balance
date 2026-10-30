<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintCertificateRepository;

class PropertyCarbonFootprintCertificateRepositoryMother
{
    public static function inMemory(): InMemoryPropertyCarbonFootprintCertificateRepository
    {
        return new InMemoryPropertyCarbonFootprintCertificateRepository();
    }
}
