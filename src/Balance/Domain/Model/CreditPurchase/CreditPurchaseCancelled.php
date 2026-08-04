<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CreditPurchaseCancelled extends DomainEvent
{
    protected static string $eventName = 'creditPurchaseCancelled';

    public function __construct(
        private CreditPurchaseId $creditPurchaseId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function creditPurchaseId(): CreditPurchaseId
    {
        return $this->creditPurchaseId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->creditPurchaseId();
    }

    public function payload(): array
    {
        return [
            'creditPurchaseId' => (string) $this->creditPurchaseId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CreditPurchaseCancelled(
            creditPurchaseId: new CreditPurchaseId($payload['creditPurchaseId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
