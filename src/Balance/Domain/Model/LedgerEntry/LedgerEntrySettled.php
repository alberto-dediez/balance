<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\DomainEvent;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class LedgerEntrySettled extends DomainEvent
{
    protected static string $eventName = 'ledgerEntrySettled';

    public function __construct(
        private LedgerEntryId $ledgerEntryId,
        private PayoutId $payoutId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function ledgerEntryId(): LedgerEntryId
    {
        return $this->ledgerEntryId;
    }

    public function payoutId(): PayoutId
    {
        return $this->payoutId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->ledgerEntryId();
    }

    public function payload(): array
    {
        return [
            'ledgerEntryId' => (string) $this->ledgerEntryId,
            'payoutId' => (string) $this->payoutId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new LedgerEntrySettled(
            ledgerEntryId: new LedgerEntryId($payload['ledgerEntryId']),
            payoutId: new PayoutId($payload['payoutId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
