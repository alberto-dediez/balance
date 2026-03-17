<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class SecurityUser extends User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    public function getRoles(): array
    {
        $roles = [];
        
        foreach ($this->roles() as $role) {
            $roles[] = $role->value;
        }

        return $roles;
    }

    public function eraseCredentials(): void
    {
        $this->password = '';
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password();
    }
    
    public function isEqualTo(UserInterface $user): bool
    {
        return $user->getUserIdentifier() === $this->getUserIdentifier() && $user->getRoles() == $this->getRoles();
    }

    public function __construct(User $user)
    {
        parent::__construct(
            id: $user->id(), 
            username: $user->username(),
            password: $user->password(),
            accountId: $user->accountId(),
            account: $user->account(),
            roles: $user->roles(),
            events: $user->recordedEvents(),
            registeredAt: $user->registeredAt()
        );
    }
}