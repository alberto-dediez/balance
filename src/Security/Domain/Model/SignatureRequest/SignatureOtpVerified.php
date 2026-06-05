<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SignatureOtpVerified extends DomainEvent
{
    protected static string $eventName = 'signatureOtpVerified';

    public function __construct(
        private SignatureRequestId $signatureRequestId,
        private \DateTimeImmutable $verifiedAt,
        private string $signerIp = '',
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function signatureRequestId(): SignatureRequestId
    {
        return $this->signatureRequestId;
    }

    public function verifiedAt(): \DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function signerIp(): string
    {
        return $this->signerIp;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->signatureRequestId();
    }

    public function payload(): array
    {
        return [
            'signatureRequestId' => (string) $this->signatureRequestId,
            'verifiedAt' => $this->verifiedAt->format(\DateTimeInterface::ATOM),
            'signerIp' => $this->signerIp,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SignatureOtpVerified(
            signatureRequestId: new SignatureRequestId($payload['signatureRequestId']),
            verifiedAt: new \DateTimeImmutable($payload['verifiedAt']),
            signerIp: $payload['signerIp'] ?? '',
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
