<?php

namespace App\Waste\Domain\Model\Measurement;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Container\MeasurementMethod;

class Measurement
{
    use AggregateRoot;

    private array $events = [];

    public function __construct(
        private MeasurementId $id, 
        private ContainerId|string|null $containerId = null,
        private WasteId|string|null $wasteId = null,
        private ?Purpose $purpose = null,
        private ?MeasurementMethod $measurementMethod = null,
        private int $quantity = 0,
        private float $load = 0,
        private ?string $justification = null,
        private ?UserId $registeredBy = null,
        private ?\DateTimeImmutable $registeredAt = null,
        array $events = []
    ) {

    }

    public function id(): MeasurementId
    {
        return $this->id;
    }

    public function containerId() : ?ContainerId
    {
        if (is_string($this->containerId)) {
            $this->containerId = new ContainerId($this->containerId);
        }

        return $this->containerId;
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

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function load(): float
    {
        return $this->load;
    }

    public function justification(): ?string
    {
        return $this->justification;
    }

    public function registeredBy(): ?UserId
    {
        return $this->registeredBy;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function register(
        Container $container,
        int $quantity,
        float $load,
        UserId $registeredBy,
        ?string $justification = null
    ) {
        $this->recordApplyAndPublish(
            new MeasurementRegistered(
                measurementId: $this->id(),
                containerId: $container->id(),
                wasteId: $container->wasteId(),
                purpose: $container->purpose(),
                measurementMethod: $container->measurementMethod(),
                quantity: $quantity,
                load: $load,
                justification: $justification,
                registeredBy: $registeredBy
            )
        );
    }

    public function update(
        int $quantity,
        float $load,
        ?string $justification = null
    ) {
        $this->recordApplyAndPublish(
            new MeasurementUpdated(
                measurementId: $this->id(),
                quantity: $quantity,
                load: $load,
                justification: $justification
            )
        );
    }

    public function remove()
    {
        $this->recordApplyAndPublish(
            new MeasurementRemoved(
                measurementId: $this->id()
            )
        );
    }

    public function applyMeasurementRegistered(MeasurementRegistered $event)
    {
        $this->containerId = $event->containerId();
        $this->wasteId = $event->wasteId();
        $this->purpose = $event->purpose();
        $this->measurementMethod = $event->measurementMethod();
        $this->quantity = $event->quantity();
        $this->load = $event->load();
        $this->justification = $event->justification();
        $this->registeredBy = $event->registeredBy();
        $this->registeredAt = new \DateTimeImmutable("NOW");
    }

    public function applyMeasurementUpdated(MeasurementUpdated $event)
    {
        $this->quantity = $event->quantity();
        $this->load = $event->load();
        $this->justification = $event->justification();
    }

    public function applyMeasurementRemoved(MeasurementRemoved $event)
    {
        
    }

    public function __toString(): string
    {
        return (string) $this->id();
    }
}