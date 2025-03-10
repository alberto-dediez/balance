<?php

namespace App\Waste\Domain\Model\Property;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;

final class PropertyRemoved extends DomainEvent
{
    protected string $eventName = 'propertyRemoved';

    public function __construct(
        private PropertyId $propertyId, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function payload(): ?array
    {
        return [
            'propertyId' => (string) $this->propertyId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new PropertyRemoved(
            propertyId: new PropertyId($payload['propertyId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}