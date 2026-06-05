<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Pdf;

use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\PdfSigner;
use App\Security\Domain\Model\SignatureRequest\SignatureProof;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\VerificationResult;
use setasign\Fpdi\Tcpdf\Fpdi;

class HmacPdfSigner implements PdfSigner
{
    private const DRIVER_NAME = 'hmac';
    private const HKDF_INFO = 'pdf-signature';
    private const HKDF_LENGTH = 32;

    public function __construct(
        #[\SensitiveParameter]
        private string $appSecret,
    ) {}

    public function sign(DocumentReference $source, string $destinationPath, SignatureRequest $request, \DateTimeImmutable $signedAt, ?string $acceptanceTextSha256 = null): string
    {
        $sourceFile = $source->path();
        if (null === $sourceFile || !is_readable($sourceFile)) {
            throw new \RuntimeException('El documento fuente no existe o no es legible.');
        }

        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setAutoPageBreak(true, 15);
        $pdf->setMargins(15, 15, 15, true);

        $compatPath = null;

        try {
            $this->importPages($pdf, $sourceFile);
        } catch (\setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException $e) {
            $compatPath = $this->downgradeWithGhostscript($sourceFile);
            $this->importPages($pdf, $compatPath);
        }

        try {
            $this->appendAttestationPage($pdf, $request, $source, $signedAt, $acceptanceTextSha256);
            $pdf->Output($destinationPath, 'F');
        } finally {
            if (null !== $compatPath) {
                @unlink($compatPath);
            }
        }

        $fileHash = hash_file('sha256', $destinationPath);
        if (false === $fileHash) {
            throw new \RuntimeException('No se pudo calcular el hash SHA-256 del documento firmado.');
        }

        return $this->computeHmac($fileHash);
    }

    public function verify(string $signedPdfPath, SignatureProof $proof): VerificationResult
    {
        if (!is_readable($signedPdfPath)) {
            return new VerificationResult(valid: false, details: 'El documento firmado no se encontró o no es legible.');
        }

        $currentHash = hash_file('sha256', $signedPdfPath);
        assert(false !== $currentHash);

        if (!hash_equals($proof->signedDocumentSha256() ?? '', $currentHash)) {
            return new VerificationResult(valid: false, details: 'El documento ha sido modificado desde que fue firmado.');
        }

        $expectedHmac = $this->computeHmac($currentHash);

        if (!hash_equals($proof->proofPayload() ?? '', $expectedHmac)) {
            return new VerificationResult(valid: false, details: 'La firma HMAC no es válida.');
        }

        return new VerificationResult(valid: true, details: 'Firma HMAC verificada correctamente.');
    }

    public function driverName(): string
    {
        return self::DRIVER_NAME;
    }

    private function appendAttestationPage(
        Fpdi $pdf,
        SignatureRequest $request,
        DocumentReference $source,
        \DateTimeImmutable $signedAt,
        ?string $acceptanceTextSha256,
    ): void {
        $pdf->AddPage();
        $pdf->setFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Certificado de firma electronica', 0, 1, 'L');
        $pdf->Ln(2);

        $signer = $request->signer();

        $rows = [
            'Identificador de firma' => (string) $request->id(),
            'Driver criptografico' => self::DRIVER_NAME,
            'Firmante' => $signer->name(),
            'Email firmante' => $signer->email() ?? 'n/a',
            'Fecha y hora (UTC)' => $signedAt->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::ATOM),
            'Hash documento original (SHA-256)' => $source->sha256() ?? 'n/a',
            'Documento original' => $source->filename() ?? 'document.pdf',
            'Hash del texto de aceptacion (SHA-256)' => $acceptanceTextSha256 ?? 'n/a',
        ];

        foreach ($rows as $label => $value) {
            $pdf->setFont('helvetica', 'B', 9);
            $pdf->Cell(70, 6, $label, 0, 0, 'L');
            $pdf->setFont('helvetica', '', 9);
            $pdf->MultiCell(0, 6, $value, 0, 'L');
        }

        $pdf->Ln(4);
        $pdf->setFont('helvetica', 'I', 8);
        $pdf->MultiCell(0, 5, 'Este documento ha sido firmado electronicamente. Cualquier modificacion posterior invalidara la firma.', 0, 'L');
    }

    private function importPages(Fpdi $pdf, string $sourcePath): void
    {
        $pageCount = $pdf->setSourceFile($sourcePath);
        for ($i = 1; $i <= $pageCount; ++$i) {
            $template = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($template);
            if (is_array($size)) {
                $pdf->AddPage($size['orientation'] ?? 'P', [$size['width'] ?? 210, $size['height'] ?? 297]);
            } else {
                $pdf->AddPage();
            }
            $pdf->useTemplate($template, ['adjustPageSize' => true]);
        }
    }

    private function downgradeWithGhostscript(string $sourcePath): string
    {
        if (null === shell_exec('which gs 2>/dev/null')) {
            throw new \RuntimeException('El PDF usa compresión no soportada por FPDI y Ghostscript (gs) no está instalado. Instálalo con: apt-get install ghostscript');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'fpdi_compat_') . '.pdf';

        $exitCode = 0;
        exec(sprintf(
            'gs -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=%s %s 2>/dev/null',
            escapeshellarg($tempPath),
            escapeshellarg($sourcePath),
        ), result_code: $exitCode);

        if (0 !== $exitCode || !is_readable($tempPath) || 0 === filesize($tempPath)) {
            @unlink($tempPath);

            throw new \RuntimeException(sprintf('Ghostscript no pudo procesar el PDF: %s', basename($sourcePath)));
        }

        return $tempPath;
    }

    private function computeHmac(string $fileHash): string
    {
        $derivedKey = hash_hkdf('sha256', $this->appSecret, self::HKDF_LENGTH, self::HKDF_INFO);

        return hash_hmac('sha256', $fileHash, $derivedKey);
    }
}
