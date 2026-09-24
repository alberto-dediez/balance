<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security\TwoFactor;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class TwoFactorToken implements TokenInterface
{
    public const TARGET_PATH_ATTRIBUTE = 'two_factor_target_path';

    public function __construct(
        private TokenInterface $authenticatedToken,
        private string $firewallName,
        private bool $rememberMeRequested,
    ) {}

    public function authenticatedToken(): TokenInterface
    {
        return $this->authenticatedToken;
    }

    public function firewallName(): string
    {
        return $this->firewallName;
    }

    public function rememberMeRequested(): bool
    {
        return $this->rememberMeRequested;
    }

    public function __toString(): string
    {
        return sprintf('TwoFactorToken(user="%s", firewall="%s")', $this->getUserIdentifier(), $this->firewallName);
    }

    public function getUserIdentifier(): string
    {
        return $this->authenticatedToken->getUserIdentifier();
    }

    public function getRoleNames(): array
    {
        return [];
    }

    public function getUser(): ?UserInterface
    {
        return $this->authenticatedToken->getUser();
    }

    public function setUser(UserInterface $user): void
    {
        $this->authenticatedToken->setUser($user);
    }

    public function eraseCredentials(): void {}

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->authenticatedToken->getAttributes();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->authenticatedToken->setAttributes($attributes);
    }

    public function hasAttribute(string $name): bool
    {
        return $this->authenticatedToken->hasAttribute($name);
    }

    public function getAttribute(string $name): mixed
    {
        return $this->authenticatedToken->getAttribute($name);
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->authenticatedToken->setAttribute($name, $value);
    }

    /**
     * @return array{TokenInterface, string, bool}
     */
    public function __serialize(): array
    {
        return [$this->authenticatedToken, $this->firewallName, $this->rememberMeRequested];
    }

    /**
     * @param array{TokenInterface, string, bool} $data
     */
    public function __unserialize(array $data): void
    {
        [$this->authenticatedToken, $this->firewallName, $this->rememberMeRequested] = $data;
    }
}
