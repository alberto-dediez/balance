<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Pdf;

use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\SignatureProof;
use App\Security\Infrastructure\Pdf\HmacPdfSigner;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use PHPUnit\Framework\TestCase;

class HmacPdfSignerTest extends TestCase
{
    private HmacPdfSigner $signer;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->signer = new HmacPdfSigner(appSecret: 'test-app-secret');
    }

    public function testSignAndVerifyRoundtrip(): void
    {
        $sourcePath = $this->createMinimalPdf();
        $destPath = tempnam(sys_get_temp_dir(), 'pdf_dst_') . '.pdf';

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $source = DocumentReference::fromPath($sourcePath);

        $proofPayload = $this->signer->sign($source, $destPath, $signatureRequest, new \DateTimeImmutable());

        $this->assertNotEmpty($proofPayload);
        $this->assertFileExists($destPath);

        $signedDocumentSha256 = hash_file('sha256', $destPath);
        $this->assertNotFalse($signedDocumentSha256);

        $proof = SignatureProof::create(
            signedAt: new \DateTimeImmutable(),
            documentHashAtSigning: $source->sha256() ?? '',
            signedDocumentSha256: $signedDocumentSha256,
            driver: 'hmac',
            proofPayload: $proofPayload,
        );

        $result = $this->signer->verify($destPath, $proof);

        $this->assertTrue($result->valid);

        @unlink($sourcePath);
        @unlink($destPath);
    }

    public function testVerifyFailsAfterModification(): void
    {
        $sourcePath = $this->createMinimalPdf();
        $destPath = tempnam(sys_get_temp_dir(), 'pdf_dst_') . '.pdf';

        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $source = DocumentReference::fromPath($sourcePath);

        $proofPayload = $this->signer->sign($source, $destPath, $signatureRequest, new \DateTimeImmutable());

        $signedDocumentSha256 = hash_file('sha256', $destPath);
        $this->assertNotFalse($signedDocumentSha256);

        // Modify the signed document
        file_put_contents($destPath, '%PDF-1.4 tampered content', FILE_APPEND);

        $proof = SignatureProof::create(
            signedAt: new \DateTimeImmutable(),
            documentHashAtSigning: $source->sha256() ?? '',
            signedDocumentSha256: $signedDocumentSha256,
            driver: 'hmac',
            proofPayload: $proofPayload,
        );

        $result = $this->signer->verify($destPath, $proof);

        $this->assertFalse($result->valid);

        @unlink($sourcePath);
        @unlink($destPath);
    }

    private function createMinimalPdf(): string
    {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Test document', 0, 1);
        $path = tempnam(sys_get_temp_dir(), 'pdf_src_') . '.pdf';
        $pdf->Output($path, 'F');

        return $path;
    }

    public function testVerifyFailsForMissingFile(): void
    {
        $proof = SignatureProof::create(
            signedAt: new \DateTimeImmutable(),
            documentHashAtSigning: 'h',
            signedDocumentSha256: 'h2',
            driver: 'hmac',
            proofPayload: 'p',
        );

        $result = $this->signer->verify('/nonexistent/path/signed.pdf', $proof);

        $this->assertFalse($result->valid);
    }
}
