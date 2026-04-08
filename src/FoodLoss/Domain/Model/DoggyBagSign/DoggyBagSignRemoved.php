<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DoggyBagSign;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DoggyBagSignRemoved extends DomainEvent
{
    protected static string $eventName = 'doggyBagSignRemoved';

    public function __construct(
        private DoggyBagSignId $doggyBagSignId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function doggyBagSignId(): DoggyBagSignId
    {
        return $this->doggyBagSignId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->doggyBagSignId();
    }

    public function payload(): array
    {
        return [
            'doggyBagSignId' => (string) $this->doggyBagSignId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DoggyBagSignRemoved(
            doggyBagSignId: new DoggyBagSignId($payload['doggyBagSignId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
