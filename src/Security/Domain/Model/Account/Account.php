<?php

namespace App\Security\Domain\Model\Account;

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
        protected array $events = [],
        protected ?\DateTimeImmutable $registeredAt = null
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

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function register(string $name, bool $active)
    {
        $this->recordApplyAndPublish(
            new AccountRegistered(
                $this->id(),
                $name,
                $active
            )
        );
    }

    public function applyAccountRegistered(AccountRegistered $event)
    {
        $this->name = $event->name();
        $this->active = $event->active();

        $this->registeredAt = $event->occurredOn();
    }
}