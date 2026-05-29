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
        private ?OtpChallenge $otpChallenge = null,
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

    public function isSigned(): bool
    {
        return SignatureRequestStatus::Signed === $this->status();
    }

    public function isExpired(): bool
    {
        return SignatureRequestStatus::Expired === $this->status();
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

    public function recordView(string $signerIp, string $signerUserAgent): void
    {
        $this->recordApplyAndPublish(
            new SignatureDocumentViewed(
                signatureRequestId: $this->id(),
                signerIp: $signerIp,
                signerUserAgent: $signerUserAgent,
            ),
        );
    }

    public function sign(SignatureProof $proof, DocumentReference $documentSigned): void
    {
        if (SignatureRequestStatus::Signed === $this->status) {
            throw new SignatureAlreadySignedException($this->id());
        }

        if ($this->removed || SignatureRequestStatus::Expired === $this->status) {
            throw new SignatureRequestNotPendingException($this->id());
        }

        $this->recordApplyAndPublish(
            new DocumentSigned(
                signatureRequestId: $this->id(),
                signatureProof: $proof,
                documentSigned: $documentSigned,
            ),
        );
    }

    public function expire(): void
    {
        $this->recordApplyAndPublish(
            new SignatureRequestExpired(
                signatureRequestId: $this->id(),
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

    public function sendOtp(OtpChallenge $challenge): void
    {
        $signer = $this->signer;
        $channel = $signer->notificationChannel();
        $isSms = NotificationChannel::Sms === $channel;
        $actualDestination = $isSms ? ($signer->phone() ?? '') : ($signer->email() ?? '');
        $maskedDestination = $isSms
            ? $this->maskPhone($actualDestination)
            : $this->maskEmail($actualDestination);

        $this->recordApplyAndPublish(
            new SignatureOtpRequested(
                signatureRequestId: $this->id(),
                notificationChannel: $channel,
                maskedDestination: $maskedDestination,
                expiresAt: $challenge->expiresAt() ?? new \DateTimeImmutable('+15 minutes'),
                otpChallenge: $challenge,
                actualDestination: $actualDestination,
            ),
        );
    }

    public function verifyOtp(string $code, #[\SensitiveParameter] string $appSecret, string $signerIp = ''): bool
    {
        if (null === $this->otpChallenge) {
            return false;
        }

        $verified = $this->otpChallenge->verify($code, $appSecret);

        if ($verified) {
            $verifiedAt = new \DateTimeImmutable('NOW');
            $this->recordApplyAndPublish(
                new SignatureOtpVerified(
                    signatureRequestId: $this->id(),
                    verifiedAt: $verifiedAt,
                    signerIp: $signerIp,
                ),
            );
        } else {
            $this->otpChallenge = $this->otpChallenge->withFailedAttempt();
        }

        return $verified;
    }

    public function otpChallenge(): ?OtpChallenge
    {
        return $this->otpChallenge;
    }

    public function isOtpVerified(): bool
    {
        return $this->otpChallenge?->isVerified() ?? false;
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

    public function applySignatureDocumentViewed(SignatureDocumentViewed $event): void
    {
        if (SignatureRequestStatus::Pending === $this->status) {
            $this->status = SignatureRequestStatus::Viewed;
        }
        $this->updatedAt = $event->occurredOn();
    }

    public function applyDocumentSigned(DocumentSigned $event): void
    {
        $this->status = SignatureRequestStatus::Signed;
        $this->signatureProof = $event->signatureProof();
        $this->documentSigned = $event->documentSigned();
        $this->updatedAt = $event->occurredOn();
    }

    public function applySignatureRequestExpired(SignatureRequestExpired $event): void
    {
        $this->status = SignatureRequestStatus::Expired;
        $this->updatedAt = $event->occurredOn();
    }

    public function applySignatureRequestRemoved(SignatureRequestRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applySignatureOtpRequested(SignatureOtpRequested $event): void
    {
        $this->otpChallenge = $event->otpChallenge();
        $this->updatedAt = $event->occurredOn();
    }

    public function applySignatureOtpVerified(SignatureOtpVerified $event): void
    {
        $this->otpChallenge = $this->otpChallenge?->withVerified($event->verifiedAt());
        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id();
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        $local = $parts[0];
        $domain = $parts[1] ?? '';
        $masked = substr($local, 0, 1) . str_repeat('*', max(0, strlen($local) - 1));

        return $masked . '@' . $domain;
    }

    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);

        return str_repeat('*', max(0, $len - 3)) . substr($phone, -3);
    }
}
