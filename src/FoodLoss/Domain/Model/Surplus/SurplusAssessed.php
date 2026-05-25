<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SurplusAssessed extends DomainEvent
{
    protected static string $eventName = 'surplusAssessed';

    public function __construct(
        private readonly SurplusId $surplusId,
        private readonly SurplusDestination $destination,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusId(): SurplusId
    {
        return $this->surplusId;
    }

    public function destination(): SurplusDestination
    {
        return $this->destination;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->surplusId;
    }

    public function payload(): array
    {
        return [
            'surplusId' => (string) $this->surplusId,
            'destination' => $this->destination->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SurplusAssessed(
            surplusId: new SurplusId($payload['surplusId']),
            destination: SurplusDestination::from($payload['destination']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
