<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

use App\Materiality\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialityTopicRemoved extends DomainEvent
{
    protected static string $eventName = 'materialityTopicRemoved';

    public function __construct(
        private MaterialityTopicId $materialityTopicId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityTopicId(): MaterialityTopicId
    {
        return $this->materialityTopicId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityTopicId();
    }

    public function payload(): array
    {
        return [
            'materialityTopicId' => (string) $this->materialityTopicId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialityTopicRemoved(
            materialityTopicId: new MaterialityTopicId($payload['materialityTopicId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
