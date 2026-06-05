<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest\Verify;

use App\Security\Application\SignatureRequest\Verify\VerifySignatureRequest;
use App\Security\Application\SignatureRequest\Verify\VerifySignatureService;
use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\PdfSigner;
use App\Security\Domain\Model\SignatureRequest\SignatureProof;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Security\Domain\Model\SignatureRequest\VerificationResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use PHPUnit\Framework\TestCase;

class VerifySignatureServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testReturnsNoVerificationResultWhenNotSigned(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $repository->add($signatureRequest);

        $pdfSigner = $this->createMock(PdfSigner::class);
        $pdfSigner->expects($this->never())->method('verify');

        $sut = new VerifySignatureService(repository: $repository, pdfSigner: $pdfSigner);

        $response = $sut(new VerifySignatureRequest($signatureRequest->id()));

        $this->assertEquals(SignatureRequestStatus::Pending, $response->signatureRequest->status());
        $this->assertNull($response->verificationResult);
    }

    public function testVerifiesSignedDocument(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();

        $proof = SignatureProof::create(
            signedAt: new \DateTimeImmutable(),
            documentHashAtSigning: 'original-hash',
            signedDocumentSha256: 'signed-hash',
            driver: 'hmac',
            proofPayload: 'hmac-proof',
            signerIp: '127.0.0.1',
        );
        $doc = new DocumentReference(sha256: 'signed-hash', size: 100, filename: 'signed.pdf', path: '/tmp/signed.pdf');
        $signatureRequest->sign($proof, $doc);
        $repository->add($signatureRequest);

        $pdfSigner = $this->createMock(PdfSigner::class);
        $pdfSigner->method('verify')->willReturn(new VerificationResult(valid: true, details: 'OK'));

        $sut = new VerifySignatureService(repository: $repository, pdfSigner: $pdfSigner);

        $response = $sut(new VerifySignatureRequest($signatureRequest->id()));

        $this->assertNotNull($response->verificationResult);
        $this->assertTrue($response->verificationResult->valid);
    }

    public function testThrowsWhenNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $repository = SignatureRequestRepositoryMother::inMemory();
        $pdfSigner = $this->createMock(PdfSigner::class);

        $sut = new VerifySignatureService(repository: $repository, pdfSigner: $pdfSigner);
        $sut(new VerifySignatureRequest(\App\Security\Domain\Model\SignatureRequest\SignatureRequestId::generate()));
    }
}
