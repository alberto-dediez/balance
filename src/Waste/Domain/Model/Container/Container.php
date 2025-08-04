<?php

namespace App\Waste\Domain\Model\Container;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class Container
{
    use AggregateRoot;

    private array $events = [];

    public function __construct(
        private ContainerId $id, 
        private AccountId|string|null $accountId = null,
        private PropertyId|string|null $propertyId = null,
        private WasteId|string|null $wasteId = null,
        private ?Purpose $purpose = null,
        private ?MeasurementMethod $measurementMethod = null,
        private ?string $volume = '0',
        private WasteManagerId|string|null $wasteManagerId = null,
        private ?QrCode $qrCode = null,
        private bool $removed = false,
        array $events = [],
        private ?\DateTimeImmutable $registeredAt = null,
        private ?\DateTimeImmutable $printedAt = null,
        private ?\DateTimeImmutable $removedAt = null
    ) {

    }

    public function id(): ContainerId
    {
        return $this->id;
    }

    public function accountId() : ?AccountId
    {
        if (is_string($this->accountId)) {
            $this->accountId = new AccountId($this->accountId);
        }

        return $this->accountId;
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

    public function volume(): ?string
    {
        return $this->volume;
    }

    public function wasteManagerId() : ?WasteManagerId
    {
        if (is_string($this->wasteManagerId)) {
            $this->wasteManagerId = new WasteManagerId($this->wasteManagerId);
        }

        return $this->wasteManagerId;
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

    public function printedAt(): \DateTimeImmutable
    {
        return $this->printedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        AccountId $accountId,
        PropertyId $propertyId,
        WasteId $wasteId,
        Purpose $purpose,
        MeasurementMethod $measurementMethod,
        ?string $volume,
        WasteManagerId $wasteManagerId,
        QrCode $qrCode
    ) {
        if ($measurementMethod == MeasurementMethod::Volume && (null === $volume || $volume < 0)) {
            throw new NotValidVolumeException($volume);
        }

        $this->recordApplyAndPublish(
            new ContainerRegistered(
                containerId: $this->id(),
                accountId: $accountId,
                propertyId: $propertyId,
                wasteId: $wasteId,
                purpose: $purpose,
                measurementMethod: $measurementMethod,
                volume: $volume,
                wasteManagerId: $wasteManagerId,
                qrCode: $qrCode
            )
        );
    }

    public function update(
        WasteId $wasteId,
        Purpose $purpose,
        MeasurementMethod $measurementMethod,
        WasteManagerId $wasteManagerId,
        ?string $volume
    ) {
        if ($measurementMethod == MeasurementMethod::Volume && (null === $volume || $volume < 0)) {
            throw new NotValidVolumeException($volume);
        }

        $this->recordApplyAndPublish(
            new ContainerUpdated(
                containerId: $this->id(),
                wasteId: $wasteId,
                purpose: $purpose,
                measurementMethod: $measurementMethod,
                volume: $volume,
                wasteManagerId: $wasteManagerId
            )
        );
    }

    public function printQr(QrPdfGenerator $pdfGenerator): string
    {
        $path = $pdfGenerator->generate($this, $this->qrCode());

        $this->recordApplyAndPublish(
            new ContainerQrPrinted(
                containerId: $this->id(),
                path: $path
            )
        );

        return $path;
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
        $this->accountId = $event->accountId();
        $this->propertyId = $event->propertyId();
        $this->wasteId = $event->wasteId();
        $this->purpose = $event->purpose();
        $this->measurementMethod = $event->measurementMethod();
        $this->volume = $event->volume();
        $this->wasteManagerId = $event->wasteManagerId();
        $this->qrCode = $event->qrCode();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyContainerUpdated(ContainerUpdated $event)
    {
        $this->wasteId = $event->wasteId();
        $this->purpose = $event->purpose();
        $this->measurementMethod = $event->measurementMethod();
        $this->volume = $event->volume();
        $this->wasteManagerId = $event->wasteManagerId();
    }

    public function applyContainerQrPrinted(ContainerQrPrinted $event)
    {
        $this->printedAt = new \DateTimeImmutable("NOW");
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