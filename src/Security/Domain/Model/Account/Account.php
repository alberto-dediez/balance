<?php

namespace App\Security\Domain\Model\Account;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRegistered;

class Account
{
    use AggregateRoot;

    public function __construct(
        protected AccountId $id,
        protected string $name = '',
        protected bool $active = false,
        protected bool $removed = false,
        array $events = [],
        protected \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        protected ?\DateTimeImmutable $removedAt = null
    ) {

    }

    public function id(): AccountId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isRemoved(): bool
    {
        return true == $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(string $name, bool $active)
    {
        $this->recordApplyAndPublish(
            new AccountRegistered(
                accountId: $this->id(),
                name: $name,
                active: $active
            )
        );
    }

    public function changeName(string $name)
    {
        $this->recordApplyAndPublish(
            new AccountNameChanged(
                accountId: $this->id(),
                name: $name
            )
        );
    }

    public function changeStatus(bool $active)
    {
        $this->recordApplyAndPublish(
            new AccountStatusChanged(
                accountId: $this->id(),
                active: $active
            )
        );
    }

    public function remove()
    {
        $this->recordApplyAndPublish(
            new AccountRemoved(
                accountId: $this->id()
            )
        );
    }

    public function applyAccountRegistered(AccountRegistered $event)
    {
        $this->name = $event->name();
        $this->active = $event->active();

        $this->registeredAt = $event->occurredOn();
    }

    public function applyAccountNameChanged(AccountNameChanged $event)
    {
        $this->name = $event->name();
    }

    public function applyAccountStatusChanged(AccountStatusChanged $event)
    {
        $this->active = $event->active();
    }

    public function applyAccountRemoved(AccountRemoved $event)
    {
        $this->removed = true;
        $this->removedAt = new \DateTimeImmutable("NOW");
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
