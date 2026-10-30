<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStatus;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrinePropertyCarbonFootprintCertificateRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePropertyCarbonFootprintCertificateRepositoryTest extends PropertyCarbonFootprintCertificateRepositoryTest
{
    public function testIsRevokedReadsTheDatabaseInsteadOfTheLoadedEntity(): void
    {
        $repository = $this->createSUT();

        $certificate = PropertyCarbonFootprintCertificateMother::issued();
        $repository->add($certificate);

        $this->manager()->getConnection()->executeStatement(
            'UPDATE property_carbon_footprint_certificates SET status = :status WHERE id = :id',
            ['status' => PropertyCarbonFootprintCertificateStatus::Revoked->value, 'id' => (string) $certificate->id()],
        );

        $loaded = $repository->find($certificate->id());

        $this->assertNotNull($loaded);
        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Pending, $loaded->status());
        $this->assertTrue($repository->isRevoked($certificate->id()));
    }

    protected function createSUT(): PropertyCarbonFootprintCertificateRepository
    {
        return new DoctrinePropertyCarbonFootprintCertificateRepository($this->manager());
    }

    private function manager(): EntityManagerInterface
    {
        return $this->getContainer()->get(EntityManagerInterface::class);
    }
}
