<?php

namespace App\Waste\Domain\Model\Container;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Waste\Domain\Model\Property\PropertyId;

class Container
{
    use AggregateRoot;

    private array $events = [];

    public function __construct(
        private ContainerId $id, 
        private PropertyId|string|null $propertyId = null,
        private WasteId|string|null $wasteId = null,
        private ?Purpose $purpose = null,
        private ?MeasurementMethod $measurementMethod = null,
        private ?QrCode $qrCode = null,
        private bool $removed = false,
        array $events = [],
        private ?\DateTimeImmutable $registeredAt = null,
        private ?\DateTimeImmutable $removedAt = null
    ) {

    }

    public function id(): ContainerId
    {
        return $this->id;
    }

    public function propertyId() : ?PropertyId
    {
        if (is_string($this->propertyId)) {
            $this->propertyId = new PropertyId($this->propertyId);
        }

        return $this->propertyId;
    }

    public function wasteId() : ?WasteId
    {
        if (is_string($this->wasteId)) {
            $this->wasteId = new WasteId($this->wasteId);
        }

        return $this->wasteId;
    }

    public function purpose() : ?Purpose
    {
        return $this->purpose;
    }

    public function measurementMethod() : ?MeasurementMethod
    {
        return $this->measurementMethod;
    }

    public function qrCode(): ?QrCode
    {
        return $this->qrCode;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        PropertyId $propertyId,
        WasteId $wasteId,
        Purpose $purpose,
        MeasurementMethod $measurementMethod,
        QrCodeGenerator $qrCodeGenerator
    ) {
        $this->recordApplyAndPublish(
            new ContainerRegistered(
                containerId: $this->id(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                purpose: $purpose,
                measurementMethod: $measurementMethod,
            )
        );

        $this->qrCode = $qrCodeGenerator->generate($this->id());
    }

    public function update(
        WasteId $wasteId,
        Purpose $purpose,
        MeasurementMethod $measurementMethod
    ) {
        $this->recordApplyAndPublish(
            new ContainerUpdated(
                containerId: $this->id(),
                wasteId: $wasteId,
                purpose: $purpose,
                measurementMethod: $measurementMethod,
            )
        );
    }

    public function remove()
    {
        $this->recordApplyAndPublish(
            new ContainerRemoved(
                containerId: $this->id()
            )
        );
    }

    public function applyContainerRegistered(ContainerRegistered $event)
    {
        $this->propertyId = $event->propertyId();
        $this->wasteId = $event->wasteId();
        $this->purpose = $event->purpose();
        $this->measurementMethod = $event->measurementMethod();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyContainerUpdated(ContainerUpdated $event)
    {
        $this->wasteId = $event->wasteId();
        $this->purpose = $event->purpose();
        $this->measurementMethod = $event->measurementMethod();
    }

    public function applyContainerRemoved(ContainerRemoved $event)
    {
        $this->removed = true;
        $this->removedAt = new \DateTimeImmutable("NOW");
    }

    public function __toString(): string
    {
        return (string) $this->id();
    }
}