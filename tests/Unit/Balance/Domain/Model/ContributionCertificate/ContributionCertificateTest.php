<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateFailed;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateGenerated;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateIssued;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateSent;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateStatus;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ContributionCertificateTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIssuingPublishesTheEventAndLeavesItPending(): void
    {
        $spy = new SpyDomainEventSubscriber();

        $certificate = ContributionCertificateMother::issued();

        $this->assertInstanceOf(ContributionCertificateIssued::class, $spy->lastEvent());
        $this->assertSame(ContributionCertificateStatus::Pending, $certificate->status());
        $this->assertTrue($certificate->isCompensation());
        $this->assertSame(['2024-b012'], $certificate->masterSerials());
    }

    public function testASocialCertificateCarriesNoEmissionsRegistryOrSerials(): void
    {
        $certificate = ContributionCertificateMother::issued(type: ContributionCertificateType::SocialContribution);

        $this->assertFalse($certificate->isCompensation());
        $this->assertNull($certificate->co2eqKg());
        $this->assertNull($certificate->projectRegistryId());
        $this->assertSame([], $certificate->masterSerials());
    }

    public function testGeneratingStoresTheFilesPerLocale(): void
    {
        $spy = new SpyDomainEventSubscriber();

        $certificate = ContributionCertificateMother::generated(files: [
            'es' => 'account/cert-es.pdf',
            'en' => 'account/cert-en.pdf',
        ]);

        $this->assertInstanceOf(ContributionCertificateGenerated::class, $spy->lastEvent());
        $this->assertTrue($certificate->isGenerated());
        $this->assertSame('account/cert-en.pdf', $certificate->fileFor(Locale::English));
    }

    public function testAMissingLocaleFallsBackToTheOneTheFlowUsed(): void
    {
        $certificate = ContributionCertificateMother::generated(files: ['es' => 'account/cert-es.pdf']);

        $this->assertSame('account/cert-es.pdf', $certificate->fileFor(Locale::German));
    }

    public function testFailingRecordsTheReason(): void
    {
        $spy = new SpyDomainEventSubscriber();

        $certificate = ContributionCertificateMother::issued();
        $certificate->markFailed('boom');

        $this->assertInstanceOf(ContributionCertificateFailed::class, $spy->lastEvent());
        $this->assertSame(ContributionCertificateStatus::Failed, $certificate->status());
        $this->assertSame('boom', $certificate->failureReason());
        $this->assertNotNull($certificate->failedAt());
    }

    public function testSendingIsRecordedWithoutChangingTheDocument(): void
    {
        $spy = new SpyDomainEventSubscriber();

        $certificate = ContributionCertificateMother::generated();
        $certificate->markSent('other@example.com');

        $this->assertInstanceOf(ContributionCertificateSent::class, $spy->lastEvent());
        $this->assertTrue($certificate->isGenerated());
    }

    public function testAnEmptyCodeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContributionCertificateMother::issued(code: '');
    }

    public function testAnEmptyVerificationTokenIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContributionCertificateMother::issued(verificationToken: '');
    }
}
