<?php

namespace App\Waste\Domain\Model\Container;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Event\DomainEventId;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Container\ContainerId;

final class ContainerRegistered extends DomainEvent
{
    protected string $eventName = 'containerRegistered';

    public function __construct(
        private ContainerId $containerId, 
        private PropertyId $propertyId,
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

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
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
            'propertyId' => (string) $this->propertyId,
            'waste' => (string) $this->wasteId,
            'purpose' => $this->purpose->value,
            'measurementMethod' => $this->measurementMethod->value
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new ContainerRegistered(
            containerId: new ContainerId($payload['containerId']), 
            propertyId: new PropertyId($payload['propertyId']),
            wasteId: new WasteId($payload['waste']), 
            purpose: Purpose::tryFrom($payload['purpose']),
            measurementMethod: MeasurementMethod::tryFrom($payload['measurementMethod']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}