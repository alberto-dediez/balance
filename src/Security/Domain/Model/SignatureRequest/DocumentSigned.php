<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DocumentSigned extends DomainEvent
{
    protected static string $eventName = 'documentSigned';

    public function __construct(
        private SignatureRequestId $signatureRequestId,
        private SignatureProof $signatureProof,
        private DocumentReference $documentSigned,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function signatureRequestId(): SignatureRequestId
    {
        return $this->signatureRequestId;
    }

    public function signatureProof(): SignatureProof
    {
        return $this->signatureProof;
    }

    public function documentSigned(): DocumentReference
    {
        return $this->documentSigned;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->signatureRequestId();
    }

    public function payload(): array
    {
        return [
            'signatureRequestId' => (string) $this->signatureRequestId,
            'documentSignedSha256' => $this->documentSigned->sha256(),
            'documentSignedSize' => $this->documentSigned->size(),
            'documentSignedFilename' => $this->documentSigned->filename(),
            'documentSignedPath' => $this->documentSigned->path(),
            'proofSignedAt' => $this->signatureProof->signedAt()?->format(\DateTimeInterface::ATOM),
            'proofDocumentHashAtSigning' => $this->signatureProof->documentHashAtSigning(),
            'proofSignedDocumentSha256' => $this->signatureProof->signedDocumentSha256(),
            'proofDriver' => $this->signatureProof->driver(),
            'proofPayload' => $this->signatureProof->proofPayload(),
            'proofSignerIp' => $this->signatureProof->signerIp(),
            'proofSignerUserAgent' => $this->signatureProof->signerUserAgent(),
            'proofSignerAcceptLanguage' => $this->signatureProof->signerAcceptLanguage(),
            'proofAcceptanceTextSha256' => $this->signatureProof->acceptanceTextSha256(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $signedAt = isset($payload['proofSignedAt']) ? new \DateTimeImmutable($payload['proofSignedAt']) : null;

        if (null === $signedAt) {
            throw new \RuntimeException('El payload del evento DocumentSigned no contiene proofSignedAt.');
        }

        $proof = SignatureProof::create(
            signedAt: $signedAt,
            documentHashAtSigning: $payload['proofDocumentHashAtSigning'] ?? '',
            signedDocumentSha256: $payload['proofSignedDocumentSha256'] ?? '',
            driver: $payload['proofDriver'] ?? '',
            proofPayload: $payload['proofPayload'] ?? '',
            signerIp: $payload['proofSignerIp'] ?? null,
            signerUserAgent: $payload['proofSignerUserAgent'] ?? null,
            signerAcceptLanguage: $payload['proofSignerAcceptLanguage'] ?? null,
            acceptanceTextSha256: $payload['proofAcceptanceTextSha256'] ?? null,
        );

        $documentSigned = new DocumentReference(
            sha256: $payload['documentSignedSha256'] ?? null,
            size: $payload['documentSignedSize'] ?? null,
            filename: $payload['documentSignedFilename'] ?? null,
            path: $payload['documentSignedPath'] ?? null,
        );

        return new DocumentSigned(
            signatureRequestId: new SignatureRequestId($payload['signatureRequestId']),
            signatureProof: $proof,
            documentSigned: $documentSigned,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
