<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class SignatureRequest implements \Stringable
{
    use AggregateRoot;

    private const EXPIRATION_TIME = '+14 DAYS';

    private Token $token;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private SignatureRequestId $id,
        private Signer $signer = new Signer(),
        private DocumentReference $documentToSign = new DocumentReference(),
        private ?DocumentReference $documentSigned = null,
        private ?SignatureProof $signatureProof = null,
        private SignatureRequestStatus $status = SignatureRequestStatus::Pending,
        private ?AccountId $accountId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private \DateTimeImmutable $expiresAt = new \DateTimeImmutable('NOW ' . self::EXPIRATION_TIME),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): SignatureRequestId
    {
        return $this->id;
    }

    public function token(): Token
    {
        return $this->token;
    }

    public function documentToSign(): DocumentReference
    {
        return $this->documentToSign;
    }

    public function documentSigned(): ?DocumentReference
    {
        return $this->documentSigned;
    }

    public function signer(): Signer
    {
        return $this->signer;
    }

    public function signatureProof(): ?SignatureProof
    {
        return $this->signatureProof;
    }

    public function status(): SignatureRequestStatus
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return SignatureRequestStatus::Pending === $this->status();
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        Token $token,
        DocumentReference $documentToSign,
        Signer $signer,
        ?AccountId $accountId = null,
    ): void {
        $this->recordApplyAndPublish(
            new SignatureRequestRegistered(
                signatureRequestId: $this->id(),
                token: $token,
                documentToSign: $documentToSign,
                signer: $signer,
                expiresAt: new \DateTimeImmutable('NOW ' . self::EXPIRATION_TIME),
                accountId: $accountId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new SignatureRequestRemoved(
                signatureRequestId: $this->id(),
            ),
        );
    }

    public function applySignatureRequestRegistered(SignatureRequestRegistered $event): void
    {
        $this->token = $event->token();
        $this->documentToSign = $event->documentToSign();
        $this->signer = $event->signer();
        $this->expiresAt = $event->expiresAt();
        $this->accountId = $event->accountId();
        $this->status = SignatureRequestStatus::Pending;
        $this->registeredAt = $event->occurredOn();
    }

    public function applySignatureRequestRemoved(SignatureRequestRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id();
    }
}
