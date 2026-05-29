<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

interface PdfSigner
{
    public function sign(DocumentReference $source, string $destinationPath, SignatureRequest $request): string;

    public function verify(string $signedPdfPath, SignatureProof $proof): VerificationResult;

    public function driverName(): string;
}
