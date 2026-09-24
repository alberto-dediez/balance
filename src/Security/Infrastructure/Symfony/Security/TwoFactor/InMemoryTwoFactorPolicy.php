<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security\TwoFactor;

use App\Security\Application\User\TwoFactor\TwoFactorPolicy;

final class InMemoryTwoFactorPolicy implements TwoFactorPolicy
{
    public function __construct(private bool $required = false, private bool $ssoEnabled = false) {}

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isSsoEnabled(): bool
    {
        return $this->ssoEnabled;
    }

    public function require(): void
    {
        $this->required = true;
    }

    public function makeOptional(): void
    {
        $this->required = false;
    }

    public function enableSso(): void
    {
        $this->ssoEnabled = true;
    }

    public function disableSso(): void
    {
        $this->ssoEnabled = false;
    }

    public function reset(): void
    {
        $this->required = false;
        $this->ssoEnabled = false;
    }
}
