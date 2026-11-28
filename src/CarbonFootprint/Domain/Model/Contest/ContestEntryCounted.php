<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContestEntryCounted extends DomainEvent
{
    protected static string $eventName = 'contestEntryCounted';

    public function __construct(
        private readonly ContestId $contestId,
        private readonly int $entryNumber,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contestId(): ContestId
    {
        return $this->contestId;
    }

    public function entryNumber(): int
    {
        return $this->entryNumber;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contestId;
    }

    public function payload(): array
    {
        return [
            'contestId' => (string) $this->contestId,
            'entryNumber' => $this->entryNumber,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            contestId: new ContestId($payload['contestId']),
            entryNumber: $payload['entryNumber'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
