<?php

namespace App\Waste\Domain\Model\Property;

use App\Security\Domain\Model\Account\AccountId;
use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;

final class PropertyRegistered extends DomainEvent
{
    protected string $eventName = 'propertyRegistered';

    public function __construct(
        private PropertyId $propertyId, 
        private string $name, 
        private ?AccountId $accountId = null,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function payload(): ?array
    {
        return [
            'propertyId' => (string) $this->propertyId,
            'accountId' => null !== $this->accountId ? ((string) $this->accountId) : null,
            'name' => $this->name
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new PropertyRegistered(
            propertyId: new PropertyId($payload['propertyId']), 
            accountId: new AccountId($payload['accountId']), 
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}