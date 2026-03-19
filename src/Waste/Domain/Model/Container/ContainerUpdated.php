<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Container;

use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

final class ContainerUpdated extends DomainEvent
{
    protected static string $eventName = 'containerUpdated';

    public function __construct(
        private readonly ContainerId $containerId,
        private readonly WasteId $wasteId,
        private readonly MeasurementMethod $measurementMethod,
        private readonly ?string $volume,
        private readonly Purpose $purpose,
        private readonly WasteManagerId $wasteManagerId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function containerId(): ContainerId
    {
        return $this->containerId;
    }

    public function wasteId(): WasteId
    {
        return $this->wasteId;
    }

    public function purpose(): Purpose
    {
        return $this->purpose;
    }

    public function measurementMethod(): MeasurementMethod
    {
        return $this->measurementMethod;
    }

    public function volume(): ?string
    {
        return $this->volume;
    }

    public function wasteManagerId(): WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->containerId;
    }

    public function payload(): array
    {
        return [
            'containerId' => (string) $this->containerId,
            'wasteId' => (string) $this->wasteId,
            'purpose' => $this->purpose->value,
            'measurementMethod' => $this->measurementMethod->value,
            'wasteManagerId' => (string) $this->wasteManagerId,
            'volume' => $this->volume,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ContainerUpdated(
            containerId: new ContainerId($payload['containerId']),
            wasteId: new WasteId($payload['wasteId']),
            measurementMethod: MeasurementMethod::tryFrom($payload['measurementMethod']),
            volume: $payload['volume'] ?? '0',
            purpose: Purpose::tryFrom($payload['purpose']),
            wasteManagerId: isset($payload['wasteManagerId']) ? new WasteManagerId($payload['wasteManagerId']) : WasteManagerId::generate(),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
