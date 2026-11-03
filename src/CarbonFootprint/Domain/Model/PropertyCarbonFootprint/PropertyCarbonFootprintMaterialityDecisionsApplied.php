<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PropertyCarbonFootprintMaterialityDecisionsApplied extends DomainEvent
{
    protected static string $eventName = 'propertyCarbonFootprintMaterialityDecisionsApplied';

    /** @param list<array{topicId: string, topicNumber: int|null, scorePercent: float, thresholdPercent: int, forcedByHighMagnitude: bool, verdict: string, source: string|null}> $decisions */
    public function __construct(
        private PropertyCarbonFootprintId $propertyCarbonFootprintId,
        private array $decisions,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyCarbonFootprintId(): PropertyCarbonFootprintId
    {
        return $this->propertyCarbonFootprintId;
    }

    /** @return list<array{topicId: string, topicNumber: int|null, scorePercent: float, thresholdPercent: int, forcedByHighMagnitude: bool, verdict: string, source: string|null}> */
    public function decisions(): array
    {
        return $this->decisions;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'propertyCarbonFootprintId' => (string) $this->propertyCarbonFootprintId,
            'decisions' => $this->decisions,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            propertyCarbonFootprintId: new PropertyCarbonFootprintId($payload['propertyCarbonFootprintId']),
            decisions: $payload['decisions'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
