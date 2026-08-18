<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

use App\Payments\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PayoutFailed extends DomainEvent
{
    protected static string $eventName = 'payoutFailed';

    public function __construct(
        private PayoutId $payoutId,
        private string $reason,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function payoutId(): PayoutId
    {
        return $this->payoutId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->payoutId();
    }

    public function payload(): array
    {
        return [
            'payoutId' => (string) $this->payoutId,
            'reason' => $this->reason,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PayoutFailed(
            payoutId: new PayoutId($payload['payoutId']),
            reason: (string) $payload['reason'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
