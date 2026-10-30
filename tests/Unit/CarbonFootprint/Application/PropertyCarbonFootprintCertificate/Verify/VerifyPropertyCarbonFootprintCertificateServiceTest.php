<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Verify;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Verify\VerifyPropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Verify\VerifyPropertyCarbonFootprintCertificateService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStatus;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintCertificateRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepositoryMother;
use PHPUnit\Framework\TestCase;

class VerifyPropertyCarbonFootprintCertificateServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintCertificateRepository $certificates;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->certificates = PropertyCarbonFootprintCertificateRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAnEmptyTokenVerifiesNothing(): void
    {
        $this->assertNull($this->verify('   '));
    }

    public function testAnUnknownTokenVerifiesNothing(): void
    {
        $this->certificates->add(PropertyCarbonFootprintCertificateMother::generated());

        $this->assertNull($this->verify('unknown-token'));
    }

    public function testAGeneratedCertificateIsValid(): void
    {
        $certificate = PropertyCarbonFootprintCertificateMother::generated(verificationToken: 'valid-token', stays: 41000, totalCo2eqKg: 184320.55);
        $this->certificates->add($certificate);

        $response = $this->verify('valid-token');

        $this->assertNotNull($response);
        $this->assertTrue($response->valid);
        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Generated, $response->status);
        $this->assertSame($certificate->code(), $response->code);
        $this->assertSame(4.50, $response->co2eqKgPerStay);
        $this->assertSame(2025, $response->year);
        $this->assertNull($response->revokedAt);
        $this->assertNotNull($response->accountId);
    }

    public function testARevokedCertificateIsNotValid(): void
    {
        $this->certificates->add(PropertyCarbonFootprintCertificateMother::revoked(verificationToken: 'revoked-token'));

        $response = $this->verify('revoked-token');

        $this->assertNotNull($response);
        $this->assertFalse($response->valid);
        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Revoked, $response->status);
        $this->assertNotNull($response->revokedAt);
    }

    private function verify(string $token): mixed
    {
        return (new VerifyPropertyCarbonFootprintCertificateService($this->certificates))(new VerifyPropertyCarbonFootprintCertificateRequest($token));
    }
}
