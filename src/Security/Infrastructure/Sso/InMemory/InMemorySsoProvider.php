<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso\InMemory;

use App\Security\Infrastructure\Sso\SsoAuthenticationFailedException;
use App\Security\Infrastructure\Sso\SsoIdentity;
use App\Security\Infrastructure\Sso\SsoProvider;
use Symfony\Component\HttpFoundation\Request;

final class InMemorySsoProvider implements SsoProvider
{
    private ?string $email = null;

    private bool $fails = false;

    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private bool $enabled = true,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function loginUrl(): string
    {
        return sprintf('https://sso.test/%s', $this->key);
    }

    public function authenticate(Request $request): SsoIdentity
    {
        if ($this->fails || null === $this->email) {
            throw SsoAuthenticationFailedException::fromProvider($this->key, new \RuntimeException('in-memory failure'));
        }

        return new SsoIdentity(mb_strtolower($this->email));
    }

    public function identifyAs(string $email): void
    {
        $this->email = $email;
    }

    public function fail(): void
    {
        $this->fails = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function reset(): void
    {
        $this->email = null;
        $this->fails = false;
        $this->enabled = true;
    }
}
