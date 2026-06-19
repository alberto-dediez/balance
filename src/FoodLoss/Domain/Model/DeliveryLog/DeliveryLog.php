<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\Model\RecipientOrganization\PickupType;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class DeliveryLog implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private DeliveryLogId $id,
        private \DateTimeImmutable $date = new \DateTimeImmutable('NOW'),
        private float $quantity = 0.0,
        private DeliveryLogUnit $unit = DeliveryLogUnit::Kg,
        private FoodType $productType = FoodType::Others,
        private string $batch = '',
        private ?RecipientOrganizationId $recipientOrganizationId = null,
        private ?AccountId $accountId = null,
        private ?PropertyId $propertyId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
        private ?SurplusId $surplusId = null,
        private bool $delivered = false,
        private ?\DateTimeImmutable $deliveredAt = null,
        private ?SignatureRequestId $signatureRequestId = null,
        private ?PickupType $pickupType = null,
    ) {
        $this->events = $events;
    }

    public function id(): DeliveryLogId
    {
        return $this->id;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    public function unit(): DeliveryLogUnit
    {
        return $this->unit;
    }

    public function productType(): FoodType
    {
        return $this->productType;
    }

    public function batch(): string
    {
        return $this->batch;
    }

    public function recipientOrganizationId(): ?RecipientOrganizationId
    {
        return $this->recipientOrganizationId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function surplusId(): ?SurplusId
    {
        return $this->surplusId;
    }

    public function isDelivered(): bool
    {
        return $this->delivered;
    }

    public function deliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function signatureRequestId(): ?SignatureRequestId
    {
        return $this->signatureRequestId;
    }

    public function pickupType(): ?PickupType
    {
        return $this->pickupType;
    }

    public function isInSignatureProcess(): bool
    {
        return !$this->delivered && null !== $this->signatureRequestId;
    }

    public function register(
        \DateTimeImmutable $date,
        float $quantity,
        DeliveryLogUnit $unit,
        FoodType $productType,
        string $batch,
        RecipientOrganizationId $recipientOrganizationId,
        AccountId $accountId,
        PropertyId $propertyId,
        ?SurplusId $surplusId = null,
    ): void {
        $this->recordApplyAndPublish(
            new DeliveryLogRegistered(
                deliveryLogId: $this->id(),
                date: $date,
                quantity: $quantity,
                unit: $unit,
                productType: $productType,
                batch: $batch,
                recipientOrganizationId: $recipientOrganizationId,
                accountId: $accountId,
                propertyId: $propertyId,
                surplusId: $surplusId,
            ),
        );
    }

    public function update(
        \DateTimeImmutable $date,
        float $quantity,
        DeliveryLogUnit $unit,
        FoodType $productType,
        string $batch,
        RecipientOrganizationId $recipientOrganizationId,
        PropertyId $propertyId,
    ): void {
        $this->recordApplyAndPublish(
            new DeliveryLogUpdated(
                deliveryLogId: $this->id(),
                date: $date,
                quantity: $quantity,
                unit: $unit,
                productType: $productType,
                batch: $batch,
                recipientOrganizationId: $recipientOrganizationId,
                propertyId: $propertyId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new DeliveryLogRemoved(
                deliveryLogId: $this->id(),
            ),
        );
    }

    public function deliver(\DateTimeImmutable $deliveredAt, ?PickupType $pickupType = null): void
    {
        if ($this->isDelivered()) {
            throw DeliveryLogAlreadyProcessedException::alreadyDelivered($this->id());
        }

        if ($this->isInSignatureProcess()) {
            throw DeliveryLogAlreadyProcessedException::alreadyInSignatureProcess($this->id());
        }

        $this->recordApplyAndPublish(
            new DeliveryLogDelivered(
                deliveryLogId: $this->id(),
                deliveredAt: $deliveredAt,
                pickupType: $pickupType,
            ),
        );
    }

    public function startSignatureProcess(SignatureRequestId $signatureRequestId, \DateTimeImmutable $deliveredAt, ?PickupType $pickupType = null): void
    {
        if ($this->isDelivered()) {
            throw DeliveryLogAlreadyProcessedException::alreadyDelivered($this->id());
        }

        if ($this->isInSignatureProcess()) {
            throw DeliveryLogAlreadyProcessedException::alreadyInSignatureProcess($this->id());
        }

        $this->recordApplyAndPublish(
            new DeliveryLogSignatureStarted(
                deliveryLogId: $this->id(),
                signatureRequestId: $signatureRequestId,
                deliveredAt: $deliveredAt,
                pickupType: $pickupType,
            ),
        );
    }

    public function cancelSignatureProcess(): void
    {
        if (!$this->isInSignatureProcess()) {
            throw new \InvalidArgumentException('Cannot cancel signature process on not started one.');
        }

        $this->recordApplyAndPublish(
            new DeliveryLogSignatureCancelled(
                deliveryLogId: $this->id(),
            ),
        );
    }

    public function endSignatureProcess(): void
    {
        if ($this->isDelivered()) {
            throw DeliveryLogAlreadyProcessedException::alreadyDelivered($this->id());
        }

        if (!$this->isInSignatureProcess()) {
            throw new \InvalidArgumentException('Cannot end signature process on not started one.');
        }

        $this->recordApplyAndPublish(
            new DeliveryLogSignatureEnded(
                deliveryLogId: $this->id(),
            ),
        );
    }

    public function applyDeliveryLogRegistered(DeliveryLogRegistered $event): void
    {
        $this->date = $event->date();
        $this->quantity = $event->quantity();
        $this->unit = $event->unit();
        $this->productType = $event->productType();
        $this->batch = $event->batch();
        $this->recipientOrganizationId = $event->recipientOrganizationId();
        $this->accountId = $event->accountId();
        $this->propertyId = $event->propertyId();
        $this->surplusId = $event->surplusId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyDeliveryLogUpdated(DeliveryLogUpdated $event): void
    {
        $this->date = $event->date();
        $this->quantity = $event->quantity();
        $this->unit = $event->unit();
        $this->productType = $event->productType();
        $this->batch = $event->batch();
        $this->recipientOrganizationId = $event->recipientOrganizationId();
        $this->propertyId = $event->propertyId();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyDeliveryLogRemoved(DeliveryLogRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyDeliveryLogDelivered(DeliveryLogDelivered $event): void
    {
        $this->delivered = true;
        $this->deliveredAt = $event->deliveredAt();
        $this->pickupType = $event->pickupType();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyDeliveryLogSignatureStarted(DeliveryLogSignatureStarted $event): void
    {
        $this->signatureRequestId = $event->signatureRequestId();
        $this->deliveredAt = $event->deliveredAt();
        $this->pickupType = $event->pickupType();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyDeliveryLogSignatureEnded(DeliveryLogSignatureEnded $event): void
    {
        $this->delivered = true;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyDeliveryLogSignatureCancelled(DeliveryLogSignatureCancelled $event): void
    {
        $this->signatureRequestId = null;
        $this->deliveredAt = null;
        $this->pickupType = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->date->format('Y-m-d'), $this->productType->value);
    }
}
