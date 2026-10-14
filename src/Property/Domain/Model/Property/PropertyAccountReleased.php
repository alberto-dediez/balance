<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PropertyAccountReleased extends DomainEvent
{
    protected static string $eventName = 'propertyAccountReleased';

    public function __construct(
        private readonly PropertyId $propertyId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyId;
    }

    public function payload(): array
    {
        return [
            'propertyId' => (string) $this->propertyId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PropertyAccountReleased(
            propertyId: new PropertyId($payload['propertyId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
