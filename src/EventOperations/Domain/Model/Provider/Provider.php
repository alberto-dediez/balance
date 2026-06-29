<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Provider;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\Address;
use App\Shared\Domain\Model\AggregateRoot;

class Provider implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private ProviderId $id,
        private string $name = '',
        private string $vatNumber = '',
        private Address $address = new Address(),
        private ?AccountId $accountId = null,
        private ?bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): ProviderId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function vatNumber(): string
    {
        return $this->vatNumber;
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function isRemoved(): bool
    {
        return $this->removed ?? false;
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
        string $vatNumber,
        Address $address,
        ?AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new ProviderRegistered(
                providerId: $this->id(),
                name: $name,
                vatNumber: $vatNumber,
                address: $address,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        string $name,
        string $vatNumber,
        Address $address,
    ): void {
        $this->recordApplyAndPublish(
            new ProviderUpdated(
                providerId: $this->id(),
                name: $name,
                vatNumber: $vatNumber,
                address: $address,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new ProviderRemoved(
                providerId: $this->id(),
            ),
        );
    }

    public function applyProviderRegistered(ProviderRegistered $event): void
    {
        $this->name = $event->name();
        $this->vatNumber = $event->vatNumber();
        $this->address = $event->address();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyProviderUpdated(ProviderUpdated $event): void
    {
        $this->name = $event->name();
        $this->vatNumber = $event->vatNumber();
        $this->address = $event->address();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyProviderRemoved(ProviderRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
