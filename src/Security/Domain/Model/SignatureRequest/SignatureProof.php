<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

class SignatureProof
{
    public function __construct(
        private readonly ?\DateTimeImmutable $signedAt,
        private readonly ?string $documentHashAtSigning,
        private readonly ?string $signedDocumentSha256,
        private readonly ?string $driver,
        private readonly ?string $proofPayload,
        private readonly ?string $signerIp = null,
        private readonly ?string $signerUserAgent = null,
        private readonly ?string $signerAcceptLanguage = null,
        private readonly ?string $acceptanceTextSha256 = null,
    ) {}

    public static function create(
        \DateTimeImmutable $signedAt,
        string $documentHashAtSigning,
        string $signedDocumentSha256,
        string $driver,
        string $proofPayload,
        ?string $signerIp = null,
        ?string $signerUserAgent = null,
        ?string $signerAcceptLanguage = null,
        ?string $acceptanceTextSha256 = null,
    ): self {
        return new self(
            signedAt: $signedAt,
            documentHashAtSigning: $documentHashAtSigning,
            signedDocumentSha256: $signedDocumentSha256,
            driver: $driver,
            proofPayload: $proofPayload,
            signerIp: $signerIp,
            signerUserAgent: $signerUserAgent,
            signerAcceptLanguage: $signerAcceptLanguage,
            acceptanceTextSha256: $acceptanceTextSha256,
        );
    }

    public function signedAt(): ?\DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function documentHashAtSigning(): ?string
    {
        return $this->documentHashAtSigning;
    }

    public function signedDocumentSha256(): ?string
    {
        return $this->signedDocumentSha256;
    }

    public function driver(): ?string
    {
        return $this->driver;
    }

    public function proofPayload(): ?string
    {
        return $this->proofPayload;
    }

    public function signerIp(): ?string
    {
        return $this->signerIp;
    }

    public function signerUserAgent(): ?string
    {
        return $this->signerUserAgent;
    }

    public function signerAcceptLanguage(): ?string
    {
        return $this->signerAcceptLanguage;
    }

    public function acceptanceTextSha256(): ?string
    {
        return $this->acceptanceTextSha256;
    }
}
