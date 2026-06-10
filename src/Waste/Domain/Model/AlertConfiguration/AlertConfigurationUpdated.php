<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertConfiguration;

use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;

final class AlertConfigurationUpdated extends DomainEvent
{
    protected static string $eventName = 'alertConfigurationUpdated';

    /** @param list<AlertConfigurationItem> $items */
    public function __construct(
        private readonly AlertConfigurationId $alertConfigurationId,
        private readonly array $items,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function alertConfigurationId(): AlertConfigurationId
    {
        return $this->alertConfigurationId;
    }

    /** @return list<AlertConfigurationItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->alertConfigurationId;
    }

    public function payload(): array
    {
        return [
            'alertConfigurationId' => (string) $this->alertConfigurationId,
            'items' => array_map(static fn(AlertConfigurationItem $item) => $item->toArray(), $this->items),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        /** @var list<array{id?: string, type: string, enabled: bool, propertyId: string|null, wasteId: string|null, parameters: array<string, mixed>}> $itemsData */
        $itemsData = (array) $payload['items'];

        return new AlertConfigurationUpdated(
            alertConfigurationId: new AlertConfigurationId($payload['alertConfigurationId']),
            items: array_map(static fn(array $data) => AlertConfigurationItem::fromArray($data), $itemsData),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
