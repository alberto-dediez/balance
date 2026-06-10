<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\DocumentSigned;
use App\Security\Domain\Model\SignatureRequest\SignatureAlreadySignedException;
use App\Security\Domain\Model\SignatureRequest\SignatureDocumentViewed;
use App\Security\Domain\Model\SignatureRequest\SignatureProof;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestNotPendingException;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class SignatureRequestTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testRecordViewTransitionsToPending(): void
    {
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();

        $this->assertEquals(SignatureRequestStatus::Pending, $signatureRequest->status());

        $signatureRequest->recordView(signerIp: '127.0.0.1', signerUserAgent: 'TestBrowser/1.0');

        $this->assertEquals(SignatureRequestStatus::Viewed, $signatureRequest->status());
        $this->assertInstanceOf(SignatureDocumentViewed::class, $this->spy->lastEvent());
    }

    public function testRecordViewDoesNotRegressFromView(): void
    {
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $signatureRequest->recordView('1.1.1.1', 'UA');
        $signatureRequest->recordView('2.2.2.2', 'UA2');

        $this->assertEquals(SignatureRequestStatus::Viewed, $signatureRequest->status());
    }

    public function testSignTransitionsToSigned(): void
    {
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();

        $proof = SignatureProof::create(
            signedAt: new \DateTimeImmutable(),
            documentHashAtSigning: 'abc123',
            signedDocumentSha256: 'def456',
            driver: 'hmac',
            proofPayload: 'hmacvalue',
            signerIp: '127.0.0.1',
        );

        $documentSigned = new DocumentReference(sha256: 'def456', size: 1024, filename: 'signed.pdf', path: '/tmp/signed.pdf');

        $signatureRequest->sign($proof, $documentSigned);

        $this->assertEquals(SignatureRequestStatus::Signed, $signatureRequest->status());
        $this->assertNotNull($signatureRequest->signatureProof());
        $this->assertNotNull($signatureRequest->documentSigned());
        $this->assertInstanceOf(DocumentSigned::class, $this->spy->lastEvent());
    }

    public function testSignThrowsIfAlreadySigned(): void
    {
        $this->expectException(SignatureAlreadySignedException::class);

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $proof = SignatureProof::create(new \DateTimeImmutable(), 'h', 'h', 'hmac', 'p');
        $doc = new DocumentReference();

        $signatureRequest->sign($proof, $doc);
        $signatureRequest->sign($proof, $doc);
    }

    public function testSignThrowsIfExpired(): void
    {
        $this->expectException(SignatureRequestNotPendingException::class);

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $signatureRequest->expire();

        $proof = SignatureProof::create(new \DateTimeImmutable(), 'h', 'h', 'hmac', 'p');
        $doc = new DocumentReference();

        $signatureRequest->sign($proof, $doc);
    }

    public function testExpireTransitionsToExpired(): void
    {
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $signatureRequest->expire();

        $this->assertEquals(SignatureRequestStatus::Expired, $signatureRequest->status());
    }
}
