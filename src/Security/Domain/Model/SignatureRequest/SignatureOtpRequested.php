<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SignatureOtpRequested extends DomainEvent
{
    protected static string $eventName = 'signatureOtpRequested';

    public function __construct(
        private SignatureRequestId $signatureRequestId,
        private NotificationChannel $notificationChannel,
        private string $maskedDestination,
        private \DateTimeImmutable $expiresAt,
        private OtpChallenge $otpChallenge,
        private string $actualDestination = '',
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function signatureRequestId(): SignatureRequestId
    {
        return $this->signatureRequestId;
    }

    public function notificationChannel(): NotificationChannel
    {
        return $this->notificationChannel;
    }

    public function maskedDestination(): string
    {
        return $this->maskedDestination;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function otpChallenge(): OtpChallenge
    {
        return $this->otpChallenge;
    }

    public function actualDestination(): string
    {
        return $this->actualDestination;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->signatureRequestId();
    }

    public function payload(): array
    {
        return [
            'signatureRequestId' => (string) $this->signatureRequestId,
            'notificationChannel' => $this->notificationChannel->value,
            'maskedDestination' => $this->maskedDestination,
            'expiresAt' => $this->expiresAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $notificationChannel = NotificationChannel::tryFrom($payload['notificationChannel'] ?? NotificationChannel::Email->value);

        if (null === $notificationChannel) {
            throw new \RuntimeException('Canal de notificación inválido en payload de SignatureOtpRequested.');
        }

        return new SignatureOtpRequested(
            signatureRequestId: new SignatureRequestId($payload['signatureRequestId']),
            notificationChannel: $notificationChannel,
            maskedDestination: $payload['maskedDestination'] ?? '',
            expiresAt: new \DateTimeImmutable($payload['expiresAt']),
            otpChallenge: new OtpChallenge(),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
