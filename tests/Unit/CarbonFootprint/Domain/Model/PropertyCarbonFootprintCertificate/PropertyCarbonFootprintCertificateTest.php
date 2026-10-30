<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateFailed;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateGenerated;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateIssued;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRevoked;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStatus;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class PropertyCarbonFootprintCertificateTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIssuingPublishesTheEventAndComputesTheFootprintPerStay(): void
    {
        $spy = new SpyDomainEventSubscriber();

        $certificate = PropertyCarbonFootprintCertificateMother::issued(stays: 41000, totalCo2eqKg: 184320.55);

        $this->assertInstanceOf(PropertyCarbonFootprintCertificateIssued::class, $spy->lastEvent());
        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Pending, $certificate->status());
        $this->assertSame(4.50, $certificate->co2eqKgPerStay());
        $this->assertSame(41000, $certificate->stays());
        $this->assertSame(2025, $certificate->year());
    }

    public function testTheFootprintPerStayIsRoundedToTwoDecimals(): void
    {
        $certificate = PropertyCarbonFootprintCertificateMother::issued(stays: 3, totalCo2eqKg: 100.0);

        $this->assertSame(33.33, $certificate->co2eqKgPerStay());
    }

    public function testZeroStaysAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PropertyCarbonFootprintCertificateMother::issued(stays: 0);
    }

    public function testNegativeStaysAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PropertyCarbonFootprintCertificateMother::issued(stays: -1);
    }

    public function testAnEmptyCodeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->issue(code: '', verificationToken: 'token');
    }

    public function testAnEmptyVerificationTokenIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->issue(code: 'AAAA-BBBB-CCCC', verificationToken: '');
    }

    public function testGeneratingStoresTheFilesPerLocale(): void
    {
        $spy = new SpyDomainEventSubscriber();

        $certificate = PropertyCarbonFootprintCertificateMother::generated(files: [
            'es' => 'account/certificate-es.pdf',
            'en' => 'account/certificate-en.pdf',
        ]);

        $this->assertInstanceOf(PropertyCarbonFootprintCertificateGenerated::class, $spy->lastEvent());
        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Generated, $certificate->status());
        $this->assertSame('account/certificate-en.pdf', $certificate->fileFor(Locale::English));
        $this->assertSame(['es' => 'account/certificate-es.pdf', 'en' => 'account/certificate-en.pdf'], $certificate->files());
    }

    public function testAMissingLocaleFallsBackToTheIssuingLocale(): void
    {
        $certificate = PropertyCarbonFootprintCertificateMother::generated(files: ['es' => 'account/certificate-es.pdf']);

        $this->assertSame('account/certificate-es.pdf', $certificate->fileFor(Locale::French));
    }

    public function testWithoutFilesThereIsNothingToServe(): void
    {
        $certificate = PropertyCarbonFootprintCertificateMother::issued();

        $this->assertNull($certificate->fileFor(Locale::Spanish));
    }

    public function testFailingRecordsTheReason(): void
    {
        $spy = new SpyDomainEventSubscriber();

        $certificate = PropertyCarbonFootprintCertificateMother::issued();
        $certificate->markFailed('boom');

        $this->assertInstanceOf(PropertyCarbonFootprintCertificateFailed::class, $spy->lastEvent());
        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Failed, $certificate->status());
        $this->assertSame('boom', $certificate->failureReason());
        $this->assertNotNull($certificate->failedAt());
    }

    public function testRevokingIsTerminalAndPublishedOnce(): void
    {
        $certificate = PropertyCarbonFootprintCertificateMother::generated();

        $spy = new SpyDomainEventSubscriber();

        $certificate->revoke();

        $this->assertInstanceOf(PropertyCarbonFootprintCertificateRevoked::class, $spy->lastEvent());
        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Revoked, $certificate->status());
        $this->assertTrue($certificate->isRevoked());
        $this->assertNotNull($certificate->revokedAt());

        $events = count($spy->events());

        $certificate->revoke();

        $this->assertCount($events, $spy->events());
    }

    public function testARevokedCertificateCannotBeMarkedAsGenerated(): void
    {
        $certificate = PropertyCarbonFootprintCertificateMother::issued();
        $certificate->revoke();

        $spy = new SpyDomainEventSubscriber();

        $certificate->markGenerated(['es' => 'account/certificate-es.pdf']);

        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Revoked, $certificate->status());
        $this->assertSame([], $certificate->files());
        $this->assertSame([], $spy->events());
    }

    public function testARevokedCertificateCannotBeMarkedAsFailed(): void
    {
        $certificate = PropertyCarbonFootprintCertificateMother::revoked();

        $spy = new SpyDomainEventSubscriber();

        $certificate->markFailed('boom');

        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Revoked, $certificate->status());
        $this->assertNull($certificate->failureReason());
        $this->assertSame([], $spy->events());
    }

    public function testOnlyAGeneratedCertificateIsValid(): void
    {
        $this->assertFalse(PropertyCarbonFootprintCertificateMother::issued()->isValid());
        $this->assertTrue(PropertyCarbonFootprintCertificateMother::generated()->isValid());
        $this->assertFalse(PropertyCarbonFootprintCertificateMother::revoked()->isValid());

        $failed = PropertyCarbonFootprintCertificateMother::issued();
        $failed->markFailed('boom');

        $this->assertFalse($failed->isValid());
    }

    public function testAnExcludedScope3IsKeptAsNull(): void
    {
        $certificate = PropertyCarbonFootprintCertificateMother::issued(scope3Co2eqKg: null);

        $this->assertNull($certificate->scope3Co2eqKg());
    }

    private function issue(string $code, string $verificationToken): PropertyCarbonFootprintCertificate
    {
        $certificate = new PropertyCarbonFootprintCertificate(PropertyCarbonFootprintCertificateId::generate());

        $certificate->issue(
            propertyCarbonFootprintId: PropertyCarbonFootprintId::generate(),
            propertyId: PropertyId::generate(),
            accountId: AccountId::generate(),
            year: 2025,
            code: $code,
            verificationToken: $verificationToken,
            locale: Locale::Spanish,
            propertyName: 'Hotel',
            accountName: 'Account',
            stays: 10,
            totalCo2eqKg: 100.0,
            scope1Co2eqKg: 50.0,
            scope2Co2eqKg: 50.0,
            scope3Co2eqKg: null,
        );

        return $certificate;
    }
}
