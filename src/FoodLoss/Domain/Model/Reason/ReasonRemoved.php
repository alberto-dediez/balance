<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Reason;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ReasonRemoved extends DomainEvent
{
    protected static string $eventName = 'reasonRemoved';

    public function __construct(
        private readonly ReasonId $reasonId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function reasonId(): ReasonId
    {
        return $this->reasonId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->reasonId;
    }

    public function payload(): array
    {
        return [
            'reasonId' => (string) $this->reasonId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ReasonRemoved(
            reasonId: new ReasonId($payload['reasonId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
