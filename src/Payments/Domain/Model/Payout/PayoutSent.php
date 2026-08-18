<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

use App\Payments\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PayoutSent extends DomainEvent
{
    protected static string $eventName = 'payoutSent';

    public function __construct(
        private PayoutId $payoutId,
        private string $gateway,
        private ?string $externalId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function payoutId(): PayoutId
    {
        return $this->payoutId;
    }

    public function gateway(): string
    {
        return $this->gateway;
    }

    public function externalId(): ?string
    {
        return $this->externalId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->payoutId();
    }

    public function payload(): array
    {
        return [
            'payoutId' => (string) $this->payoutId,
            'gateway' => $this->gateway,
            'externalId' => $this->externalId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PayoutSent(
            payoutId: new PayoutId($payload['payoutId']),
            gateway: (string) $payload['gateway'],
            externalId: $payload['externalId'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
