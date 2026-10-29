<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CreditPurchaseRangeCorrected extends DomainEvent
{
    protected static string $eventName = 'creditPurchaseRangeCorrected';

    public function __construct(
        private CreditPurchaseId $creditPurchaseId,
        private float $previousOffsetTonnes,
        private float $previousUpperOffsetTonnes,
        private float $offsetTonnes,
        private float $upperOffsetTonnes,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function creditPurchaseId(): CreditPurchaseId
    {
        return $this->creditPurchaseId;
    }

    public function previousOffsetTonnes(): float
    {
        return $this->previousOffsetTonnes;
    }

    public function previousUpperOffsetTonnes(): float
    {
        return $this->previousUpperOffsetTonnes;
    }

    public function offsetTonnes(): float
    {
        return $this->offsetTonnes;
    }

    public function upperOffsetTonnes(): float
    {
        return $this->upperOffsetTonnes;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->creditPurchaseId();
    }

    public function payload(): array
    {
        return [
            'creditPurchaseId' => (string) $this->creditPurchaseId,
            'previousOffsetTonnes' => $this->previousOffsetTonnes,
            'previousUpperOffsetTonnes' => $this->previousUpperOffsetTonnes,
            'offsetTonnes' => $this->offsetTonnes,
            'upperOffsetTonnes' => $this->upperOffsetTonnes,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CreditPurchaseRangeCorrected(
            creditPurchaseId: new CreditPurchaseId($payload['creditPurchaseId']),
            previousOffsetTonnes: (float) $payload['previousOffsetTonnes'],
            previousUpperOffsetTonnes: (float) $payload['previousUpperOffsetTonnes'],
            offsetTonnes: (float) $payload['offsetTonnes'],
            upperOffsetTonnes: (float) $payload['upperOffsetTonnes'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
