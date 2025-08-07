<?php

namespace App\Property\Domain\Model\Property;

use App\Shared\Domain\Model\AggregateRoot;
use App\Security\Domain\Model\Account\AccountId;
use App\Property\Domain\Model\Property\PropertyInfo;

class Property
{
    use AggregateRoot;

    private array $events = [];

    public function __construct(
        private PropertyId $id, 
        private string $name = '',
        private ?PropertyInfo $info = null,
        private ?ExternalId $externalId = null,
        private AccountId|null $accountId = null,
        private bool $removed = false,
        array $events = [],
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

    public function info(): PropertyInfo
    {
        if (null === $this->info) {
            $this->info = new PropertyInfo();
        }

        return $this->info;
    }

    public function externalId(): ExternalId
    {
        if (null === $this->externalId) {
            $this->externalId = new ExternalId();
        }

        return $this->externalId;
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

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(string $name, ?AccountId $accountId, ?PropertyInfo $info = null, ?ExternalId $externalId = null)
    {
        $this->recordApplyAndPublish(
            new PropertyRegistered(
                propertyId: $this->id(),
                accountId: $accountId,
                name: $name,
                info: $info,
                externalId: $externalId
            )
        );
    }

    public function update(string $name, ?AccountId $accountId, ?PropertyInfo $info = null, ?ExternalId $externalId = null)
    {
        $this->recordApplyAndPublish(
            new PropertyUpdated(
                propertyId: $this->id(),
                accountId: $accountId,
                name: $name,
                info: $info,
                externalId: $externalId
            )
        );
    }

    public function match(string $name, PropertyInfo $info, ExternalId $externalId)
    {
        $this->recordApplyAndPublish(
            new PropertyMatched(
                propertyId: $this->id(),
                name: $name,
                info: $info,
                externalId: $externalId
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
        $this->accountId = $event->accountId();

        $this->info = null === $event->info() ? new PropertyInfo() : $event->info();
        $this->externalId = null === $event->externalId() ? new ExternalId() : $event->externalId();
        
        $this->registeredAt = $event->occurredOn();
    }

    public function applyPropertyMatched(PropertyMatched $event)
    {
        if (!$this->info()->equals($event->info()) || !$this->externalId()->equals($event->externalId())) {
            $this->recordApplyAndPublish(
                new PropertyUpdated(
                    propertyId: $this->id(),
                    accountId: $this->accountId(),
                    name: $this->name(),
                    info: $this->info()->combine($event->info()),
                    externalId: $this->externalId()->combine($event->externalId())
                )
            );
        }
    }

    public function applyPropertyUpdated(PropertyUpdated $event)
    {
        $this->name = $event->name();
        $this->accountId = $event->accountId();

        $this->info = null === $event->info() ? $this->info() : $event->info();
        $this->externalId = null === $event->externalId() ? $this->externalId() : $event->externalId();
    }

    public function applyPropertyRemoved(PropertyRemoved $event)
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}