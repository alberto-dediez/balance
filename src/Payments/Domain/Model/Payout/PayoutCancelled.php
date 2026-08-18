<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

use App\Payments\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PayoutCancelled extends DomainEvent
{
    protected static string $eventName = 'payoutCancelled';

    public function __construct(
        private PayoutId $payoutId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function payoutId(): PayoutId
    {
        return $this->payoutId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->payoutId();
    }

    public function payload(): array
    {
        return ['payoutId' => (string) $this->payoutId];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PayoutCancelled(
            payoutId: new PayoutId($payload['payoutId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
