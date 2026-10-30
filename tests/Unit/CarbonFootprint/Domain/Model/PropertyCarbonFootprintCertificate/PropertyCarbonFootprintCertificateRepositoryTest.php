<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PropertyCarbonFootprintCertificateRepositoryTest extends KernelTestCase
{
    public function testAddAndRemoveCertificate(): void
    {
        $repository = $this->createSUT();

        $certificate = PropertyCarbonFootprintCertificateMother::issued();

        $this->assertNull($repository->find($certificate->id()));

        $repository->add($certificate);

        $recovered = $repository->find($certificate->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($certificate->id()->equals($recovered->id()));

        $repository->remove($certificate);

        $this->assertNull($repository->find($certificate->id()));
    }

    public function testFindByCode(): void
    {
        $repository = $this->createSUT();

        $repository->add(PropertyCarbonFootprintCertificateMother::issued(code: 'AAAA-BBBB-CCCC'));
        $repository->add(PropertyCarbonFootprintCertificateMother::issued(code: 'DDDD-EEEE-FFFF'));

        $found = $repository->findByCode('DDDD-EEEE-FFFF');

        $this->assertNotNull($found);
        $this->assertSame('DDDD-EEEE-FFFF', $found->code());
        $this->assertNull($repository->findByCode('ZZZZ-ZZZZ-ZZZZ'));
    }

    public function testFindByVerificationToken(): void
    {
        $repository = $this->createSUT();

        $certificate = PropertyCarbonFootprintCertificateMother::issued();

        $repository->add($certificate);
        $repository->add(PropertyCarbonFootprintCertificateMother::issued());

        $found = $repository->findByVerificationToken($certificate->verificationToken());

        $this->assertNotNull($found);
        $this->assertTrue($certificate->id()->equals($found->id()));
        $this->assertNull($repository->findByVerificationToken('not-a-token'));
    }

    public function testFindCurrentIgnoresRevokedCertificatesAndReturnsTheLatest(): void
    {
        $repository = $this->createSUT();

        $propertyCarbonFootprintId = PropertyCarbonFootprintId::generate();

        $repository->add(PropertyCarbonFootprintCertificateMother::revoked(propertyCarbonFootprintId: $propertyCarbonFootprintId));
        $older = PropertyCarbonFootprintCertificateMother::generated(
            propertyCarbonFootprintId: $propertyCarbonFootprintId,
            issuedAt: new \DateTimeImmutable('2026-10-29 10:00:00'),
        );
        $repository->add($older);
        $latest = PropertyCarbonFootprintCertificateMother::issued(
            propertyCarbonFootprintId: $propertyCarbonFootprintId,
            issuedAt: new \DateTimeImmutable('2026-10-29 10:00:05'),
        );
        $repository->add($latest);
        $repository->add(PropertyCarbonFootprintCertificateMother::issued());

        $current = $repository->findCurrentByPropertyCarbonFootprintId($propertyCarbonFootprintId);

        $this->assertNotNull($current);
        $this->assertTrue($latest->id()->equals($current->id()));
    }

    public function testFindCurrentIsNullWhenEveryCertificateIsRevoked(): void
    {
        $repository = $this->createSUT();

        $propertyCarbonFootprintId = PropertyCarbonFootprintId::generate();

        $repository->add(PropertyCarbonFootprintCertificateMother::revoked(propertyCarbonFootprintId: $propertyCarbonFootprintId));

        $this->assertNull($repository->findCurrentByPropertyCarbonFootprintId($propertyCarbonFootprintId));
    }

    public function testIsRevoked(): void
    {
        $repository = $this->createSUT();

        $revoked = PropertyCarbonFootprintCertificateMother::revoked();
        $generated = PropertyCarbonFootprintCertificateMother::generated();

        $repository->add($revoked);
        $repository->add($generated);

        $this->assertTrue($repository->isRevoked($revoked->id()));
        $this->assertFalse($repository->isRevoked($generated->id()));
        $this->assertFalse($repository->isRevoked(PropertyCarbonFootprintCertificateId::generate()));
    }

    public function testFindByPropertyCarbonFootprintId(): void
    {
        $repository = $this->createSUT();

        $propertyCarbonFootprintId = PropertyCarbonFootprintId::generate();

        $repository->add(PropertyCarbonFootprintCertificateMother::revoked(propertyCarbonFootprintId: $propertyCarbonFootprintId));
        $repository->add(PropertyCarbonFootprintCertificateMother::generated(propertyCarbonFootprintId: $propertyCarbonFootprintId));
        $repository->add(PropertyCarbonFootprintCertificateMother::issued());

        $this->assertCount(2, $repository->findByPropertyCarbonFootprintId($propertyCarbonFootprintId));
        $this->assertSame([], $repository->findByPropertyCarbonFootprintId(PropertyCarbonFootprintId::generate()));
    }

    abstract protected function createSUT(): PropertyCarbonFootprintCertificateRepository;
}
