<?php

namespace App\Waste\Domain\Model\Container;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Event\DomainEventId;
use App\Waste\Domain\Model\Container\ContainerId;

final class ContainerUpdated extends DomainEvent
{
    protected static string $eventName = 'containerUpdated';

    public function __construct(
        private ContainerId $containerId, 
        private WasteId $wasteId,
        private MeasurementMethod $measurementMethod,
        private Purpose $purpose,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
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

    public function payload(): ?array
    {
        return [
            'containerId' => (string) $this->containerId,
            'wasteId' => (string) $this->wasteId,
            'purpose' => $this->purpose->value,
            'measurementMethod' => $this->measurementMethod->value
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new ContainerUpdated(
            containerId: new ContainerId($payload['containerId']), 
            wasteId: new WasteId($payload['wasteId']), 
            purpose: Purpose::tryFrom($payload['purpose']),
            measurementMethod: MeasurementMethod::tryFrom($payload['measurementMethod']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}