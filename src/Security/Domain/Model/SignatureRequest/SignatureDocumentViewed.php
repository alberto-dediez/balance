<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SignatureDocumentViewed extends DomainEvent
{
    protected static string $eventName = 'signatureDocumentViewed';

    public function __construct(
        private SignatureRequestId $signatureRequestId,
        private string $signerIp,
        private string $signerUserAgent,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function signatureRequestId(): SignatureRequestId
    {
        return $this->signatureRequestId;
    }

    public function signerIp(): string
    {
        return $this->signerIp;
    }

    public function signerUserAgent(): string
    {
        return $this->signerUserAgent;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->signatureRequestId();
    }

    public function payload(): array
    {
        return [
            'signatureRequestId' => (string) $this->signatureRequestId,
            'signerIp' => $this->signerIp,
            'signerUserAgent' => $this->signerUserAgent,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SignatureDocumentViewed(
            signatureRequestId: new SignatureRequestId($payload['signatureRequestId']),
            signerIp: $payload['signerIp'] ?? '',
            signerUserAgent: $payload['signerUserAgent'] ?? '',
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
