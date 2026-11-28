<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContestPublished extends DomainEvent
{
    protected static string $eventName = 'contestPublished';

    public function __construct(
        private readonly ContestId $contestId,
        private readonly UserId $publishedBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contestId(): ContestId
    {
        return $this->contestId;
    }

    public function publishedBy(): UserId
    {
        return $this->publishedBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contestId;
    }

    public function payload(): array
    {
        return [
            'contestId' => (string) $this->contestId,
            'publishedBy' => (string) $this->publishedBy,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            contestId: new ContestId($payload['contestId']),
            publishedBy: new UserId($payload['publishedBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
