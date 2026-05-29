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

        $signedAt = new \DateTimeImmutable('NOW');

        $acceptanceTextSha256 = '' !== $request->acceptanceText
            ? hash('sha256', $request->acceptanceText)
            : null;

        $proofPayload = $this->pdfSigner->sign(
            source: $signatureRequest->documentToSign(),
            destinationPath: $destinationPath,
            request: $signatureRequest,
            signedAt: $signedAt,
            acceptanceTextSha256: $acceptanceTextSha256,
        );

        $signedDocumentSha256 = hash_file('sha256', $destinationPath);
        if (false === $signedDocumentSha256) {
            throw new \RuntimeException('No se pudo calcular el hash SHA-256 del documento firmado.');
        }

        $signedDocumentSize = filesize($destinationPath);
        if (false === $signedDocumentSize) {
            throw new \RuntimeException('No se pudo obtener el tamaño del documento firmado.');
        }

        $proof = SignatureProof::create(
            signedAt: $signedAt,
            documentHashAtSigning: $documentHashAtSigning,
            signedDocumentSha256: $signedDocumentSha256,
            driver: $this->pdfSigner->driverName(),
            proofPayload: $proofPayload,
            signerIp: $request->signerIp ?: null,
            signerUserAgent: $request->signerUserAgent ?: null,
            signerAcceptLanguage: $request->signerAcceptLanguage ?: null,
            acceptanceTextSha256: $acceptanceTextSha256,
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
