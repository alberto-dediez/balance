<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Container;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Waste\Domain\Model\Measurement\Unit;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class Container implements \Stringable
{
    use AggregateRoot;

    /**
     * @param array<mixed> $events
     */
    public function __construct(
        private ContainerId $id,
        private ?AccountId $accountId = null,
        private ?PropertyId $propertyId = null,
        private ?WasteId $wasteId = null,
        private Purpose $purpose = Purpose::Waste,
        private MeasurementMethod $measurementMethod = MeasurementMethod::Volume,
        private ?string $volume = '0',
        private ?WasteManagerId $wasteManagerId = null,
        private ?QrCode $qrCode = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable(),
        private ?\DateTimeImmutable $printedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): ContainerId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
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

    public function volume(): ?string
    {
        return $this->volume;
    }

    public function unit(): Unit
    {
        $unit = Unit::tryFrom($this->measurementMethod->unit());
        assert(null !== $unit);

        return $unit;
    }

    public function wasteManagerId(): ?WasteManagerId
    {
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
        assert($this->printedAt instanceof \DateTimeImmutable);

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
        string|float|null $volume,
        WasteManagerId $wasteManagerId,
        QrCode $qrCode,
    ): void {
        if (MeasurementMethod::Volume == $measurementMethod && (null === $volume || $volume < 0)) {
            throw new NotValidVolumeException(is_float($volume) ? (string) $volume : $volume);
        }

        $this->recordApplyAndPublish(
            new ContainerRegistered(
                containerId: $this->id(),
                accountId: $accountId,
                propertyId: $propertyId,
                wasteId: $wasteId,
                measurementMethod: $measurementMethod,
                volume: null !== $volume ? (string) $volume : null,
                purpose: $purpose,
                wasteManagerId: $wasteManagerId,
                qrCode: $qrCode,
            ),
        );
    }

    public function update(
        WasteId $wasteId,
        Purpose $purpose,
        MeasurementMethod $measurementMethod,
        WasteManagerId $wasteManagerId,
        ?string $volume,
    ): void {
        if (MeasurementMethod::Volume == $measurementMethod && (null === $volume || $volume < 0)) {
            throw new NotValidVolumeException($volume);
        }

        $this->recordApplyAndPublish(
            new ContainerUpdated(
                containerId: $this->id(),
                wasteId: $wasteId,
                measurementMethod: $measurementMethod,
                volume: $volume,
                purpose: $purpose,
                wasteManagerId: $wasteManagerId,
            ),
        );
    }

    public function printQr(QrPdfGenerator $pdfGenerator): string
    {
        $qrCode = $this->qrCode();
        assert($qrCode instanceof QrCode);
        $path = $pdfGenerator->generate($this, $qrCode);

        $this->recordApplyAndPublish(
            new ContainerQrPrinted(
                containerId: $this->id(),
                path: $path,
            ),
        );

        return $path;
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new ContainerRemoved(
                containerId: $this->id(),
            ),
        );
    }

    public function applyContainerRegistered(ContainerRegistered $event): void
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

    public function applyContainerUpdated(ContainerUpdated $event): void
    {
        $this->wasteId = $event->wasteId();
        $this->purpose = $event->purpose();
        $this->measurementMethod = $event->measurementMethod();
        $this->volume = $event->volume();
        $this->wasteManagerId = $event->wasteManagerId();
    }

    public function applyContainerQrPrinted(ContainerQrPrinted $event): void
    {
        $this->printedAt = new \DateTimeImmutable('NOW');
    }

    public function applyContainerRemoved(ContainerRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = new \DateTimeImmutable('NOW');
    }

    public function __toString(): string
    {
        return (string) $this->id();
    }
}
