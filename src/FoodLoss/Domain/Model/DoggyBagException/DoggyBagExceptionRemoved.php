<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DoggyBagException;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DoggyBagExceptionRemoved extends DomainEvent
{
    protected static string $eventName = 'doggyBagExceptionRemoved';

    public function __construct(
        private DoggyBagExceptionId $doggyBagExceptionId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function doggyBagExceptionId(): DoggyBagExceptionId
    {
        return $this->doggyBagExceptionId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->doggyBagExceptionId();
    }

    public function payload(): array
    {
        return [
            'doggyBagExceptionId' => (string) $this->doggyBagExceptionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DoggyBagExceptionRemoved(
            doggyBagExceptionId: new DoggyBagExceptionId($payload['doggyBagExceptionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
