<?php

namespace App\Waste\Domain\Model\Property;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Security\Domain\Model\Account\Account;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

final class PropertyUpdated extends DomainEvent
{
    protected string $eventName = 'propertyUpdated';

    public function __construct(
        private PropertyId $propertyId, 
        private string $name, 
        private ?Account $account = null,
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

    public function account(): ?Account
    {
        return $this->account;
    }

    public function payload(): ?array
    {
        return [
            'propertyId' => (string) $this->propertyId,
            'accountId' => null !== $this->account ? ((string) $this->account()->id()) : null,
            'name' => $this->name
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new PropertyUpdated(
            propertyId: new PropertyId($payload['propertyId']), 
            account: new Account(new AccountId($payload['accountId'])), 
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}