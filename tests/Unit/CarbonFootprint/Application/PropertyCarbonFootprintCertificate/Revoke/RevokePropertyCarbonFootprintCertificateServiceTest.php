<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Revoke;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Revoke\RevokePropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Revoke\RevokePropertyCarbonFootprintCertificateService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRevoked;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStatus;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RevokePropertyCarbonFootprintCertificateServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testTheCertificateIsRevoked(): void
    {
        $certificates = PropertyCarbonFootprintCertificateRepositoryMother::inMemory();
        $certificate = PropertyCarbonFootprintCertificateMother::generated();
        $certificates->add($certificate);

        $spy = new SpyDomainEventSubscriber();

        (new RevokePropertyCarbonFootprintCertificateService($certificates))(
            new RevokePropertyCarbonFootprintCertificateRequest($certificate->id()),
            UserMother::systemUser(),
        );

        $recovered = $certificates->find($certificate->id());

        $this->assertNotNull($recovered);
        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Revoked, $recovered->status());
        $this->assertInstanceOf(PropertyCarbonFootprintCertificateRevoked::class, $spy->lastEvent());
    }

    public function testAMissingCertificateThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new RevokePropertyCarbonFootprintCertificateService(PropertyCarbonFootprintCertificateRepositoryMother::inMemory()))(
            new RevokePropertyCarbonFootprintCertificateRequest(PropertyCarbonFootprintCertificateId::generate()),
            UserMother::systemUser(),
        );
    }
}
