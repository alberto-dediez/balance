<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContestEntryDisqualified extends DomainEvent
{
    protected static string $eventName = 'contestEntryDisqualified';

    public function __construct(
        private readonly ContestEntryId $contestEntryId,
        private readonly UserId $disqualifiedBy,
        private readonly string $reason,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contestEntryId(): ContestEntryId
    {
        return $this->contestEntryId;
    }

    public function disqualifiedBy(): UserId
    {
        return $this->disqualifiedBy;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contestEntryId;
    }

    public function payload(): array
    {
        return [
            'contestEntryId' => (string) $this->contestEntryId,
            'disqualifiedBy' => (string) $this->disqualifiedBy,
            'reason' => $this->reason,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            contestEntryId: new ContestEntryId($payload['contestEntryId']),
            disqualifiedBy: new UserId($payload['disqualifiedBy']),
            reason: $payload['reason'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
