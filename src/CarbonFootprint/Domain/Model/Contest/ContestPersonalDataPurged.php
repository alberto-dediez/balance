<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContestPersonalDataPurged extends DomainEvent
{
    protected static string $eventName = 'contestPersonalDataPurged';

    public function __construct(
        private readonly ContestId $contestId,
        private readonly UserId $purgedBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contestId(): ContestId
    {
        return $this->contestId;
    }

    public function purgedBy(): UserId
    {
        return $this->purgedBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contestId;
    }

    public function payload(): array
    {
        return [
            'contestId' => (string) $this->contestId,
            'purgedBy' => (string) $this->purgedBy,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            contestId: new ContestId($payload['contestId']),
            purgedBy: new UserId($payload['purgedBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
