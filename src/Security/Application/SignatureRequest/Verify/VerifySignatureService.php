<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Verify;

use App\Security\Domain\Model\SignatureRequest\PdfSigner;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Security\Domain\Model\SignatureRequest\VerificationResult;

class VerifySignatureService
{
    public function __construct(
        private SignatureRequestRepository $repository,
        private PdfSigner $pdfSigner,
    ) {}

    public function __invoke(VerifySignatureRequest $request): VerifySignatureResponse
    {
        $signatureRequest = $this->repository->find($request->signatureRequestId);

        if (null === $signatureRequest) {
            throw new \InvalidArgumentException('Solicitud de firma no encontrada.');
        }

        if (SignatureRequestStatus::Signed !== $signatureRequest->status()) {
            return new VerifySignatureResponse(signatureRequest: $signatureRequest);
        }

        $proof = $signatureRequest->signatureProof();
        $documentSigned = $signatureRequest->documentSigned();

        if (null === $proof || null === $documentSigned || null === $documentSigned->path()) {
            return new VerifySignatureResponse(
                signatureRequest: $signatureRequest,
                verificationResult: new VerificationResult(valid: false, details: 'Datos de firma incompletos.'),
            );
        }

        $verificationResult = $this->pdfSigner->verify($documentSigned->path(), $proof);

        return new VerifySignatureResponse(
            signatureRequest: $signatureRequest,
            verificationResult: $verificationResult,
        );
    }
}
