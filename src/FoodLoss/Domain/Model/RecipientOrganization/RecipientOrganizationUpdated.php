<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\RecipientOrganization;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class RecipientOrganizationUpdated extends DomainEvent
{
    protected static string $eventName = 'recipientOrganizationUpdated';

    /** @param string[] $pickupTypes */
    public function __construct(
        private RecipientOrganizationId $recipientOrganizationId,
        private string $name,
        private RecipientOrganizationType $type,
        private string $nif,
        private string $socialReason,
        private string $address,
        private string $contactName,
        private string $contactEmail,
        private string $contactPhone,
        private string $operatingHours,
        private array $pickupTypes,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function recipientOrganizationId(): RecipientOrganizationId
    {
        return $this->recipientOrganizationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): RecipientOrganizationType
    {
        return $this->type;
    }

    public function nif(): string
    {
        return $this->nif;
    }

    public function socialReason(): string
    {
        return $this->socialReason;
    }

    public function address(): string
    {
        return $this->address;
    }

    public function contactName(): string
    {
        return $this->contactName;
    }

    public function contactEmail(): string
    {
        return $this->contactEmail;
    }

    public function contactPhone(): string
    {
        return $this->contactPhone;
    }

    public function operatingHours(): string
    {
        return $this->operatingHours;
    }

    /** @return string[] */
    public function pickupTypes(): array
    {
        return $this->pickupTypes;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->recipientOrganizationId();
    }

    public function payload(): array
    {
        return [
            'recipientOrganizationId' => (string) $this->recipientOrganizationId,
            'name' => $this->name,
            'type' => $this->type->value,
            'nif' => $this->nif,
            'socialReason' => $this->socialReason,
            'address' => $this->address,
            'contactName' => $this->contactName,
            'contactEmail' => $this->contactEmail,
            'contactPhone' => $this->contactPhone,
            'operatingHours' => $this->operatingHours,
            'pickupTypes' => $this->pickupTypes,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $type = RecipientOrganizationType::from($payload['type']);

        return new RecipientOrganizationUpdated(
            recipientOrganizationId: new RecipientOrganizationId($payload['recipientOrganizationId']),
            name: $payload['name'],
            type: $type,
            nif: $payload['nif'],
            socialReason: $payload['socialReason'],
            address: $payload['address'],
            contactName: $payload['contactName'],
            contactEmail: $payload['contactEmail'],
            contactPhone: $payload['contactPhone'],
            operatingHours: $payload['operatingHours'],
            pickupTypes: $payload['pickupTypes'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
