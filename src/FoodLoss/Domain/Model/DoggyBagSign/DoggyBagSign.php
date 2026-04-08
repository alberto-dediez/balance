<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DoggyBagSign;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class DoggyBagSign implements \Stringable
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private DoggyBagSignId $id,
        private string $name = '',
        private string $location = '',
        private string $path = '',
        private ?PropertyId $propertyId = null,
        private ?AccountId $accountId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): DoggyBagSignId
    {
        return $this->id;
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

    public function accountId(): ?AccountId
    {
        return $this->accountId;
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

    public function register(
        string $name,
        string $location,
        string $path,
        ?PropertyId $propertyId,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new DoggyBagSignRegistered(
                doggyBagSignId: $this->id(),
                name: $name,
                location: $location,
                path: $path,
                propertyId: $propertyId,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        string $name,
        string $location,
        string $path,
        ?PropertyId $propertyId,
    ): void {
        $this->recordApplyAndPublish(
            new DoggyBagSignUpdated(
                doggyBagSignId: $this->id(),
                name: $name,
                location: $location,
                path: $path,
                propertyId: $propertyId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new DoggyBagSignRemoved(
                doggyBagSignId: $this->id(),
            ),
        );
    }

    public function applyDoggyBagSignRegistered(DoggyBagSignRegistered $event): void
    {
        $this->name = $event->name();
        $this->location = $event->location();
        $this->path = $event->path();
        $this->propertyId = $event->propertyId();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyDoggyBagSignUpdated(DoggyBagSignUpdated $event): void
    {
        $this->name = $event->name();
        $this->location = $event->location();
        $this->path = $event->path();
        $this->propertyId = $event->propertyId();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyDoggyBagSignRemoved(DoggyBagSignRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
