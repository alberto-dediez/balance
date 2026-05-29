<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Sign;

use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\PdfSigner;
use App\Security\Domain\Model\SignatureRequest\SignatureFileStorage;
use App\Security\Domain\Model\SignatureRequest\SignatureProof;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use App\Security\Domain\Model\SignatureRequest\Token;

class SignDocumentService
{
    public function __construct(
        private SignatureRequestRepository $repository,
        private PdfSigner $pdfSigner,
        private SignatureFileStorage $fileStorage,
        #[\SensitiveParameter]
        private string $appSecret = '',
    ) {}

    public function __invoke(SignDocumentRequest $request): SignDocumentResponse
    {
        $tokenHash = Token::hashFor($request->tokenPlainValue, $this->appSecret);
        $signatureRequest = $this->repository->findByToken($tokenHash);

        if (null === $signatureRequest || $signatureRequest->isRemoved()) {
            throw new SignatureTokenInvalidException();
        }

        if ($signatureRequest->expiresAt() < new \DateTimeImmutable('NOW')) {
            throw new SignatureTokenInvalidException();
        }

        $destinationPath = $this->fileStorage->getSignedDocumentPath($signatureRequest->id());
        $documentHashAtSigning = $signatureRequest->documentToSign()->sha256() ?? '';

        $currentFileHash = hash_file('sha256', $signatureRequest->documentToSign()->path() ?? '');
        if (false === $currentFileHash || !hash_equals($documentHashAtSigning, $currentFileHash)) {
            throw new \RuntimeException('El documento original ha sido modificado desde que se registró la solicitud de firma.');
        }

        $proofPayload = $this->pdfSigner->sign(
            source: $signatureRequest->documentToSign(),
            destinationPath: $destinationPath,
            request: $signatureRequest,
        );

        $signedDocumentSha256 = hash_file('sha256', $destinationPath);
        assert(false !== $signedDocumentSha256);

        $signedDocumentSize = filesize($destinationPath);
        assert(false !== $signedDocumentSize);

        $proof = SignatureProof::create(
            signedAt: new \DateTimeImmutable('NOW'),
            documentHashAtSigning: $documentHashAtSigning,
            signedDocumentSha256: $signedDocumentSha256,
            driver: $this->pdfSigner->driverName(),
            proofPayload: $proofPayload,
            signerIp: $request->signerIp ?: null,
            signerUserAgent: $request->signerUserAgent ?: null,
            signerAcceptLanguage: $request->signerAcceptLanguage ?: null,
        );

        $documentSigned = new DocumentReference(
            sha256: $signedDocumentSha256,
            size: $signedDocumentSize,
            filename: 'signed.pdf',
            path: $destinationPath,
        );

        $signatureRequest->sign($proof, $documentSigned);

        $this->repository->add($signatureRequest);

        return new SignDocumentResponse(signatureRequest: $signatureRequest);
    }
}
