<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContestEntryAwarded extends DomainEvent
{
    protected static string $eventName = 'contestEntryAwarded';

    public function __construct(
        private readonly ContestEntryId $contestEntryId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contestEntryId(): ContestEntryId
    {
        return $this->contestEntryId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contestEntryId;
    }

    public function payload(): array
    {
        return [
            'contestEntryId' => (string) $this->contestEntryId,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            contestEntryId: new ContestEntryId($payload['contestEntryId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
