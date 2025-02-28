<?php

namespace App\Security\Domain\Model\User;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Security\Domain\Model\User\RoleGranted;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserRegistered;

class User
{
    use AggregateRoot;

    public function __construct(
        protected UserId $id, 
        protected string $username = '',
        protected string $password = '',
        protected AccountId|string|null $accountId = null,
        protected array $roles = [],
        protected array $events = [],
        protected ?\DateTimeImmutable $registeredAt = null
    ) {

    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function accountId(): ?AccountId
    {
        if (is_string($this->accountId)) {
            $this->accountId = new AccountId($this->accountId);
        }

        return $this->accountId;
    }

    public function roles(): array
    {
        $roles = array_merge($this->roles, [Role::User->value]);

        return array_unique($roles);
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function register(string $username, string $password, ?AccountId $accountId = null)
    {
        $this->recordApplyAndPublish(
            new UserRegistered(
                userId: $this->id(),
                username: $username,
                password: $password,
                accountId: $accountId
            )
        );
    }

    public function grantRole(Role $role)
    {
        $this->recordApplyAndPublish(
            new RoleGranted(
                $this->id(),
                $role
            )
        );
    }

    public function ungrantRole(Role $role)
    {
        $this->recordApplyAndPublish(
            new RoleUngranted(
                $this->id(),
                $role
            )
        );
    }

    public function applyUserRegistered(UserRegistered $event)
    {
        $this->username = $event->username();
        $this->password = $event->password();
        $this->accountId = $event->accountId();

        $this->registeredAt = $event->occurredOn();
    }

    public function applyRoleGranted(RoleGranted $event)
    {
        if (in_array($event->role()->value, $this->roles)) {
            return ;
        }

        $this->roles[] = $event->role()->value;
    }

    public function applyRoleUngranted(RoleUngranted $event)
    {
        if (!in_array($event->role()->value, $this->roles)) {
            return ;
        }

        $key = array_search($event->role()->value, $this->roles);

        unset($this->roles[$key]);
    }
}