<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Measurement;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class Measurement implements \Stringable
{
    use AggregateRoot;

    public function __construct(
        private MeasurementId $id,
        private ?ContainerId $containerId = null,
        private ?PropertyId $propertyId = null,
        private ?WasteId $wasteId = null,
        private Purpose $purpose = Purpose::Waste,
        private MeasurementMethod $measurementMethod = MeasurementMethod::Volume,
        private ?WasteManagerId $wasteManagerId = null,
        private int $quantity = 0,
        private float|string $load = 0,
        private Unit $unit = Unit::Kilogram,
        private ?string $justification = null,
        private ?string $reference = null,
        private ?AccountId $accountId = null,
        private ?UserId $registeredBy = null,
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable(),
    ) {}

    public function id(): MeasurementId
    {
        return $this->id;
    }

    public function containerId(): ?ContainerId
    {
        return $this->containerId;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function wasteId(): ?WasteId
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

    public function wasteManagerId(): ?WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function load(): float
    {
        return (float) $this->load;
    }

    public function unit(): ?Unit
    {
        return $this->unit;
    }

    public function day(): \DateTimeImmutable
    {
        return $this->registeredAt();
    }

    public function justification(): ?string
    {
        return $this->justification;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
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
        AccountId $accountId,
        ?string $justification = null,
        ?string $reference = null,
        ?Unit $unit = null,
        ?\DateTimeImmutable $day = null,
    ): void {
        $propertyId = $container->propertyId();
        assert($propertyId instanceof PropertyId);
        $wasteId = $container->wasteId();
        assert($wasteId instanceof WasteId);
        $measurementMethod = $container->measurementMethod();
        $purpose = $container->purpose();
        $wasteManagerId = $container->wasteManagerId();
        assert($wasteManagerId instanceof WasteManagerId);

        $this->recordApplyAndPublish(
            new MeasurementRegistered(
                measurementId: $this->id(),
                containerId: $container->id(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                measurementMethod: $measurementMethod,
                purpose: $purpose,
                wasteManagerId: $wasteManagerId,
                registeredBy: $registeredBy,
                accountId: $accountId,
                day: $day ?? new \DateTimeImmutable('NOW'),
                quantity: $quantity,
                load: $load,
                unit: $unit ?? $container->unit(),
                justification: $justification,
                reference: $reference,
            ),
        );
    }

    public function update(
        int $quantity,
        float $load,
        ?string $justification = null,
        ?string $reference = null,
    ): void {
        $this->recordApplyAndPublish(
            new MeasurementUpdated(
                measurementId: $this->id(),
                quantity: $quantity,
                load: $load,
                justification: $justification,
                reference: $reference,
            ),
        );
    }

    public function remove(): void
    {
        $propertyId = $this->propertyId();
        assert($propertyId instanceof PropertyId);
        $wasteId = $this->wasteId();
        assert($wasteId instanceof WasteId);
        $purpose = $this->purpose();
        $wasteManagerId = $this->wasteManagerId();
        assert($wasteManagerId instanceof WasteManagerId);
        $accountId = $this->accountId();
        assert($accountId instanceof AccountId);

        $this->recordApplyAndPublish(
            new MeasurementRemoved(
                measurementId: $this->id(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                purpose: $purpose,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $this->day(),
            ),
        );
    }

    public function applyMeasurementRegistered(MeasurementRegistered $event): void
    {
        $this->containerId = $event->containerId();
        $this->propertyId = $event->propertyId();
        $this->wasteId = $event->wasteId();
        $this->purpose = $event->purpose();
        $this->measurementMethod = $event->measurementMethod();
        $this->wasteManagerId = $event->wasteManagerId();
        $this->quantity = $event->quantity();
        $this->load = $event->load();
        $this->unit = $event->unit();
        $this->justification = $event->justification();
        $this->reference = $event->reference();
        $this->accountId = $event->accountId();
        $this->registeredBy = $event->registeredBy();
        $this->registeredAt = $event->day();
    }

    public function applyMeasurementUpdated(MeasurementUpdated $event): void
    {
        $this->quantity = $event->quantity();
        $this->load = $event->load();
        $this->justification = $event->justification();
        $this->reference = $event->reference();
    }

    public function applyMeasurementRemoved(MeasurementRemoved $event): void {}

    public function __toString(): string
    {
        return (string) $this->id();
    }
}
