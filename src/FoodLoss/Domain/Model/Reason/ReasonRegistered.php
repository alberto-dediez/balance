<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Reason;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ReasonRegistered extends DomainEvent
{
    protected static string $eventName = 'reasonRegistered';

    public function __construct(
        private readonly ReasonId $reasonId,
        private readonly string $description,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function reasonId(): ReasonId
    {
        return $this->reasonId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->reasonId;
    }

    public function payload(): array
    {
        return [
            'reasonId' => (string) $this->reasonId,
            'description' => $this->description,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ReasonRegistered(
            reasonId: new ReasonId($payload['reasonId']),
            description: $payload['description'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
