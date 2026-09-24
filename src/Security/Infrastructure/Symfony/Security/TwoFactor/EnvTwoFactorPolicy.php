<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security\TwoFactor;

use App\Security\Application\User\TwoFactor\TwoFactorPolicy;

final class EnvTwoFactorPolicy implements TwoFactorPolicy
{
    public function __construct(private readonly bool $twoFactorRequired, private readonly bool $twoFactorSsoEnabled) {}

    public function isRequired(): bool
    {
        return $this->twoFactorRequired;
    }

    public function isSsoEnabled(): bool
    {
        return $this->twoFactorSsoEnabled;
    }
}
