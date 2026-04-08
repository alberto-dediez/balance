<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DoggyBagSign;

use App\FoodLoss\Domain\DomainEvent;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DoggyBagSignRegistered extends DomainEvent
{
    protected static string $eventName = 'doggyBagSignRegistered';

    public function __construct(
        private DoggyBagSignId $doggyBagSignId,
        private string $name,
        private string $location,
        private string $path,
        private ?PropertyId $propertyId,
        private AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function doggyBagSignId(): DoggyBagSignId
    {
        return $this->doggyBagSignId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function location(): string
    {
        return $this->location;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->doggyBagSignId();
    }

    public function payload(): array
    {
        return [
            'doggyBagSignId' => (string) $this->doggyBagSignId,
            'name' => $this->name,
            'location' => $this->location,
            'path' => $this->path,
            'propertyId' => null !== $this->propertyId ? (string) $this->propertyId : null,
            'accountId' => (string) $this->accountId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DoggyBagSignRegistered(
            doggyBagSignId: new DoggyBagSignId($payload['doggyBagSignId']),
            name: $payload['name'],
            location: $payload['location'],
            path: $payload['path'],
            propertyId: isset($payload['propertyId']) ? new PropertyId($payload['propertyId']) : null,
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
