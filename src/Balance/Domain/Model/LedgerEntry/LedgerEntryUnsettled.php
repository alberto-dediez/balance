<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class LedgerEntryUnsettled extends DomainEvent
{
    protected static string $eventName = 'ledgerEntryUnsettled';

    public function __construct(
        private LedgerEntryId $ledgerEntryId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function ledgerEntryId(): LedgerEntryId
    {
        return $this->ledgerEntryId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->ledgerEntryId();
    }

    public function payload(): array
    {
        return [
            'ledgerEntryId' => (string) $this->ledgerEntryId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new LedgerEntryUnsettled(
            ledgerEntryId: new LedgerEntryId($payload['ledgerEntryId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
