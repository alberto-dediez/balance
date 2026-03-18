<?php

namespace App\Security\Domain\Model\User;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\RoleGranted;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\RecoverToken;
use App\Security\Domain\Model\User\UserRegistered;
use App\Security\Domain\Model\User\PasswordRecovered;

class User implements \Stringable
{
    use AggregateRoot;

    /**
     * @param Role[] $roles
     * @param DomainEvent[] $events
     */
    public function __construct(
        protected UserId $id,
        protected string $username = '',
        protected string $password = '',
        protected AccountId|null $accountId = null,
        protected Account|null $account = null,
        protected array $roles = [],
        protected RecoverToken|null $recoverToken = null,
        array $events = [],
        protected bool $removed = false,
        protected \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        protected ?\DateTimeImmutable $removedAt = null
    ) {
        $this->events = $events;
    }

    public function __toString(): string
    {
        return $this->username();
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

    public function account(): ?Account
    {
        return $this->account;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function recoverToken(): ?RecoverToken
    {
        return $this->recoverToken;
    }

    public function isRecoverTokenValid(RecoverToken $token): bool
    {
        return null !== $this->recoverToken() && $this->recoverToken()->equals($token) && !$this->recoverToken()->isExpired();
    }

    /**
     * @return Role[]
     */
    public function roles(): array
    {
        if (count($this->roles) === 0) {
            return [Role::User];
        }

        $roles = $this->roles;

        if (false === $this->hasRole(Role::User)) {
            $roles[] = Role::User;
        }

        return array_values($roles);
    }

    public function hasRole(Role $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function isActive(): bool
    {
        return null == $this->account() ? true : $this->account()->isActive();
    }

    public function registeredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(string $username, string $password, ?AccountId $accountId = null): void
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

    public function grantRole(Role $role): void
    {
        $this->recordApplyAndPublish(
            new RoleGranted(
                $this->id(),
                $role
            )
        );
    }

    public function ungrantRole(Role $role): void
    {
        $this->recordApplyAndPublish(
            new RoleUngranted(
                $this->id(),
                $role
            )
        );
    }

    public function changePassword(string $password): void
    {
        $this->recordApplyAndPublish(
            new PasswordChanged(
                userId: $this->id(),
                password: $password
            )
        );
    }

    public function initPasswordRecovery(): void
    {
        $this->recordApplyAndPublish(
            new PasswordRecoveryInitialized(
                userId: $this->id(),
                username: $this->username(),
                token: RecoverToken::generate()
            )
        );
    }

    public function recoverPassword(RecoverToken $token, string $password): void
    {
        if (!$this->isRecoverTokenValid($token)) {
            throw new RecoverTokenInvalidException($token);
        }

        $this->recordApplyAndPublish(
            new PasswordRecovered(
                userId: $this->id(),
                password: $password,
                token: $token
            )
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new UserRemoved(
                $this->id()
            )
        );
    }

    public function applyUserRegistered(UserRegistered $event): void
    {
        $this->username = $event->username();
        $this->password = $event->password();
        $this->accountId = $event->accountId();

        $this->registeredAt = $event->occurredOn();
    }

    public function applyRoleGranted(RoleGranted $event): void
    {
        if (in_array($event->role(), $this->roles)) {
            return ;
        }

        $this->roles[] = $event->role();
    }

    public function applyRoleUngranted(RoleUngranted $event): void
    {
        if (!in_array($event->role(), $this->roles)) {
            return ;
        }

        $key = array_search($event->role(), $this->roles);

        unset($this->roles[$key]);
    }

    public function applyPasswordChanged(PasswordChanged $event): void
    {
        $this->password = $event->password();
    }

    public function applyPasswordRecoveryInitialized(PasswordRecoveryInitialized $event): void
    {
        $this->recoverToken = $event->token();
    }

    public function applyPasswordRecovered(PasswordRecovered $event): void
    {
        $this->password = $event->password();
        $this->recoverToken = null;
    }

    public function applyUserRemoved(UserRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = new \DateTimeImmutable("NOW");
    }
}
