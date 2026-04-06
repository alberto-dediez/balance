<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DeliveryLogUpdated extends DomainEvent
{
    protected static string $eventName = 'deliveryLogUpdated';

    public function __construct(
        private DeliveryLogId $deliveryLogId,
        private \DateTimeImmutable $date,
        private float $quantity,
        private DeliveryLogUnit $unit,
        private string $productType,
        private string $batch,
        private RecipientOrganizationId $recipientOrganizationId,
        private PropertyId $propertyId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function deliveryLogId(): DeliveryLogId
    {
        return $this->deliveryLogId;
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

    public function productType(): string
    {
        return $this->productType;
    }

    public function batch(): string
    {
        return $this->batch;
    }

    public function recipientOrganizationId(): RecipientOrganizationId
    {
        return $this->recipientOrganizationId;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->deliveryLogId();
    }

    public function payload(): array
    {
        return [
            'deliveryLogId' => (string) $this->deliveryLogId,
            'date' => $this->date->format(\DateTimeInterface::ATOM),
            'quantity' => $this->quantity,
            'unit' => $this->unit->value,
            'productType' => $this->productType,
            'batch' => $this->batch,
            'recipientOrganizationId' => (string) $this->recipientOrganizationId,
            'propertyId' => (string) $this->propertyId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DeliveryLogUpdated(
            deliveryLogId: new DeliveryLogId($payload['deliveryLogId']),
            date: new \DateTimeImmutable($payload['date']),
            quantity: (float) $payload['quantity'],
            unit: DeliveryLogUnit::from($payload['unit']),
            productType: $payload['productType'],
            batch: $payload['batch'],
            recipientOrganizationId: new RecipientOrganizationId($payload['recipientOrganizationId']),
            propertyId: new PropertyId($payload['propertyId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
