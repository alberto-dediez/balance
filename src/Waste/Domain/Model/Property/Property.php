<?php

namespace App\Waste\Domain\Model\Property;

use App\Shared\Domain\Model\AggregateRoot;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;

class Property
{
    use AggregateRoot;

    public function __construct(
        private PropertyId $id, 
        private string $name = '',
        private AccountId|string|null $accountId = null,
        private Account|null $account = null, 
        private bool $removed = false,
        private array $events = [],
        private ?\DateTimeImmutable $registeredAt = null,
        private ?\DateTimeImmutable $removedAt = null
    ) {

    }

    public function id(): PropertyId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function accountId(): ?AccountId
    {
        if (is_string($this->accountId)) {
            $this->accountId = new AccountId($this->accountId);
        }

        return $this->accountId;
    }

    public function account(): ?Account
    {
        return $this->account;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(string $name, ?Account $account)
    {
        $this->recordApplyAndPublish(
            new PropertyRegistered(
                propertyId: $this->id(),
                account: $account,
                name: $name
            )
        );
    }

    public function update(string $name, ?Account $account)
    {
        $this->recordApplyAndPublish(
            new PropertyUpdated(
                propertyId: $this->id(),
                account: $account,
                name: $name
            )
        );
    }

    public function remove()
    {
        $this->recordApplyAndPublish(
            new PropertyRemoved(
                propertyId: $this->id()
            )
        );
    }

    public function applyPropertyRegistered(PropertyRegistered $event)
    {
        $this->name = $event->name();
        $this->accountId = null !== $event->account() ? $event->account()->id() : null;
        $this->account = $event->account();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyPropertyUpdated(PropertyUpdated $event)
    {
        $this->name = $event->name();
        $this->accountId = null !== $event->account() ? $event->account()->id() : null;
        $this->account = $event->account();
    }

    public function applyPropertyRemoved(PropertyRemoved $event)
    {
        $this->removed = true;
        $this->removedAt = new \DateTimeImmutable("NOW");
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}