<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\WasteManager;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class WasteManagerUpdated extends DomainEvent
{
    protected static string $eventName = 'wasteManagerUpdated';

    public function __construct(
        private WasteManagerId $wasteManagerId,
        private string $name,
        private bool $animalFeedAuthorized,
        private bool $compostAuthorized,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function wasteManagerId(): WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function animalFeedAuthorized(): bool
    {
        return $this->animalFeedAuthorized;
    }

    public function compostAuthorized(): bool
    {
        return $this->compostAuthorized;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->wasteManagerId();
    }

    public function payload(): array
    {
        return [
            'wasteManagerId' => (string) $this->wasteManagerId,
            'name' => $this->name,
            'animalFeedAuthorized' => $this->animalFeedAuthorized,
            'compostAuthorized' => $this->compostAuthorized,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new WasteManagerUpdated(
            wasteManagerId: new WasteManagerId($payload['wasteManagerId']),
            name: $payload['name'],
            animalFeedAuthorized: (bool) $payload['animalFeedAuthorized'],
            compostAuthorized: (bool) $payload['compostAuthorized'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
