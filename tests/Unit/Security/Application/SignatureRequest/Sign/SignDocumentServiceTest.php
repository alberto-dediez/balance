<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest\Sign;

use App\Security\Application\SignatureRequest\Sign\SignDocumentRequest;
use App\Security\Application\SignatureRequest\Sign\SignDocumentService;
use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\DocumentSigned;
use App\Security\Domain\Model\SignatureRequest\PdfSigner;
use App\Security\Domain\Model\SignatureRequest\SignatureFileStorage;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use App\Security\Domain\Model\SignatureRequest\VerificationResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class SignDocumentServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testSignsDocumentSuccessfully(): void
    {
        $tmpPdf = tempnam(sys_get_temp_dir(), 'test_') . '.pdf';
        file_put_contents($tmpPdf, 'test-document-content'); // must match sha256 used in SignatureRequestMother::from()

        $tmpDest = tempnam(sys_get_temp_dir(), 'signed_') . '.pdf';

        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::from(
            id: (string) SignatureRequestId::generate(),
            documentPath: $tmpPdf,
        );
        $token = $signatureRequest->token();
        $repository->add($signatureRequest);

        $pdfSigner = new class implements PdfSigner {
            public function sign(DocumentReference $source, string $destinationPath, SignatureRequest $request, \DateTimeImmutable $signedAt, ?string $acceptanceTextSha256 = null): string
            {
                $path = $source->path();
                assert(null !== $path);
                copy($path, $destinationPath);

                return 'test-hmac-proof';
            }

            public function verify(string $signedPdfPath, \App\Security\Domain\Model\SignatureRequest\SignatureProof $proof): VerificationResult
            {
                return new VerificationResult(valid: true);
            }

            public function driverName(): string
            {
                return 'test';
            }
        };

        $fileStorage = new class ($tmpDest) implements SignatureFileStorage {
            public function __construct(private string $path) {}

            public function getSignedDocumentPath(SignatureRequestId $id): string
            {
                return $this->path;
            }
        };

        $sut = new SignDocumentService(
            repository: $repository,
            pdfSigner: $pdfSigner,
            fileStorage: $fileStorage,
            appSecret: 'test-app-secret',
        );

        $response = $sut(new SignDocumentRequest(
            tokenPlainValue: $token->plainValue(),
            signerIp: '127.0.0.1',
            signerUserAgent: 'TestBrowser',
        ));

        $this->assertEquals(SignatureRequestStatus::Signed, $response->signatureRequest->status());
        $this->assertNotNull($response->signatureRequest->signatureProof());
        $this->assertInstanceOf(DocumentSigned::class, $this->spy->lastEvent());

        @unlink($tmpPdf);
        @unlink($tmpDest);
    }

    public function testThrowsWhenTokenNotFound(): void
    {
        $this->expectException(SignatureTokenInvalidException::class);

        $repository = SignatureRequestRepositoryMother::inMemory();

        $pdfSigner = $this->createMock(PdfSigner::class);
        $fileStorage = $this->createMock(SignatureFileStorage::class);

        $sut = new SignDocumentService(
            repository: $repository,
            pdfSigner: $pdfSigner,
            fileStorage: $fileStorage,
            appSecret: 'test-app-secret',
        );

        $sut(new SignDocumentRequest(tokenPlainValue: 'nonexistent'));
    }

    public function testThrowsWhenDocumentHashMismatch(): void
    {
        $this->expectException(\RuntimeException::class);

        $tmpPdf = tempnam(sys_get_temp_dir(), 'tampered_') . '.pdf';
        file_put_contents($tmpPdf, 'tampered content that does not match registered hash');

        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::from(
            id: (string) SignatureRequestId::generate(),
            documentPath: $tmpPdf,
        );
        $token = $signatureRequest->token();
        $repository->add($signatureRequest);

        $pdfSigner = $this->createMock(PdfSigner::class);
        $fileStorage = $this->createMock(SignatureFileStorage::class);

        $sut = new SignDocumentService(
            repository: $repository,
            pdfSigner: $pdfSigner,
            fileStorage: $fileStorage,
            appSecret: 'test-app-secret',
        );

        try {
            $sut(new SignDocumentRequest(tokenPlainValue: $token->plainValue()));
        } finally {
            @unlink($tmpPdf);
        }
    }
}
