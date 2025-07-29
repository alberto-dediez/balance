<?php

namespace App\Waste\Domain\Model\Measurement;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\Container;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class Measurement
{
    use AggregateRoot;

    private array $events = [];

    public function __construct(
        private MeasurementId $id, 
        private ContainerId|string|null $containerId = null,
        private PropertyId|string|null $propertyId = null,
        private WasteId|string|null $wasteId = null,
        private ?Purpose $purpose = null,
        private ?MeasurementMethod $measurementMethod = null,
        private WasteManagerId|string|null $wasteManagerId = null,
        private int $quantity = 0,
        private float $load = 0,
        private ?string $justification = null,
        private AccountId|string|null $accountId = null,
        private UserId|string|null $registeredBy = null,
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

    public function wasteManagerId() : ?WasteManagerId
    {
        if (is_string($this->wasteManagerId)) {
            $this->wasteManagerId = new WasteManagerId($this->wasteManagerId);
        }

        return $this->wasteManagerId;
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

    public function accountId(): ?AccountId
    {
        if (is_string($this->accountId)) {
            $this->accountId = new AccountId($this->accountId);
        }

        return $this->accountId;
    }

    public function registeredBy(): ?UserId
    {
        if (is_string($this->registeredBy)) {
            $this->registeredBy = new UserId($this->registeredBy);
        }

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
        AccountId $accountId,
        ?string $justification = null
    ) {
        $this->recordApplyAndPublish(
            new MeasurementRegistered(
                measurementId: $this->id(),
                containerId: $container->id(),
                propertyId: $container->propertyId(),
                wasteId: $container->wasteId(),
                purpose: $container->purpose(),
                measurementMethod: $container->measurementMethod(),
                wasteManagerId: $container->wasteManagerId(),
                quantity: $quantity,
                load: $load,
                justification: $justification,
                accountId: $accountId,
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
        $this->propertyId = $event->propertyId();
        $this->wasteId = $event->wasteId();
        $this->purpose = $event->purpose();
        $this->measurementMethod = $event->measurementMethod();
        $this->wasteManagerId = $event->wasteManagerId();
        $this->quantity = $event->quantity();
        $this->load = $event->load();
        $this->justification = $event->justification();
        $this->accountId = $event->accountId();
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