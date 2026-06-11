<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class SignatureRequestRegistered extends DomainEvent
{
    protected static string $eventName = 'signatureRequestRegistered';

    public function __construct(
        private SignatureRequestId $signatureRequestId,
        private Token $token,
        private DocumentReference $documentToSign,
        private Signer $signer,
        private \DateTimeImmutable $expiresAt,
        private ?AccountId $accountId = null,
        private bool $otpRequired = true,
        private ?UserId $requestedBy = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function signatureRequestId(): SignatureRequestId
    {
        return $this->signatureRequestId;
    }

    public function token(): Token
    {
        return $this->token;
    }

    public function documentToSign(): DocumentReference
    {
        return $this->documentToSign;
    }

    public function signer(): Signer
    {
        return $this->signer;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function otpRequired(): bool
    {
        return $this->otpRequired;
    }

    public function requestedBy(): ?UserId
    {
        return $this->requestedBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->signatureRequestId();
    }

    public function payload(): array
    {
        return [
            'signatureRequestId' => (string) $this->signatureRequestId,
            'accountId' => (string) $this->accountId,
            'tokenHash' => $this->token->hash(),
            'documentToSignSha256' => $this->documentToSign->sha256(),
            'documentToSignSize' => $this->documentToSign->size(),
            'documentToSignFilename' => $this->documentToSign->filename(),
            'documentToSignPath' => $this->documentToSign->path(),
            'signerName' => $this->signer->name(),
            'signerLocale' => $this->signer->locale()->value,
            'signerEmail' => $this->signer->email(),
            'signerPhone' => $this->signer->phone(),
            'signerNotificationChannel' => $this->signer->notificationChannel()->value,
            'expiresAt' => $this->expiresAt->format(\DateTimeInterface::ATOM),
            'otpRequired' => $this->otpRequired,
            'requestedBy' => null !== $this->requestedBy ? (string) $this->requestedBy : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $locale = Locale::tryFrom($payload['signerLocale']);
        assert(null !== $locale);

        $notificationChannel = NotificationChannel::tryFrom($payload['signerNotificationChannel'] ?? NotificationChannel::Email->value);
        assert(null !== $notificationChannel);

        $signer = new Signer(
            name: $payload['signerName'],
            locale: $locale,
            email: $payload['signerEmail'],
            phone: $payload['signerPhone'],
            notificationChannel: $notificationChannel,
        );

        return new SignatureRequestRegistered(
            signatureRequestId: new SignatureRequestId($payload['signatureRequestId']),
            token: new Token(hash: $payload['tokenHash']),
            documentToSign: new DocumentReference(
                sha256: $payload['documentToSignSha256'],
                size: $payload['documentToSignSize'],
                filename: $payload['documentToSignFilename'],
                path: $payload['documentToSignPath'],
            ),
            signer: $signer,
            expiresAt: new \DateTimeImmutable($payload['expiresAt']),
            accountId: null !== $payload['accountId'] ? new AccountId($payload['accountId']) : null,
            otpRequired: $payload['otpRequired'] ?? true,
            requestedBy: isset($payload['requestedBy']) ? new UserId($payload['requestedBy']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
