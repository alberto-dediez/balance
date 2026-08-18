<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class LedgerCheckCompleted extends DomainEvent
{
    protected static string $eventName = 'ledgerCheckCompleted';

    /** @param list<array{code: string, subjectId: string, detail: string}> $findings */
    public function __construct(
        private LedgerEntryId $ledgerCheckId,
        private array $findings,
        private int $checkedContributions,
        private ?\DateTimeImmutable $from = null,
        private ?\DateTimeImmutable $to = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    /** @return list<array{code: string, subjectId: string, detail: string}> */
    public function findings(): array
    {
        return $this->findings;
    }

    public function checkedContributions(): int
    {
        return $this->checkedContributions;
    }

    public function from(): ?\DateTimeImmutable
    {
        return $this->from;
    }

    public function to(): ?\DateTimeImmutable
    {
        return $this->to;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->ledgerCheckId;
    }

    public function payload(): array
    {
        return [
            'ledgerCheckId' => (string) $this->ledgerCheckId,
            'findings' => $this->findings,
            'checkedContributions' => $this->checkedContributions,
            'from' => $this->from?->format(\DateTimeInterface::ATOM),
            'to' => $this->to?->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new LedgerCheckCompleted(
            ledgerCheckId: new LedgerEntryId($payload['ledgerCheckId']),
            findings: $payload['findings'],
            checkedContributions: (int) $payload['checkedContributions'],
            from: null !== $payload['from'] ? new \DateTimeImmutable($payload['from']) : null,
            to: null !== $payload['to'] ? new \DateTimeImmutable($payload['to']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
