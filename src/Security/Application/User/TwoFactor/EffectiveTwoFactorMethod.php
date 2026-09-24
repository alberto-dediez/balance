<?php

declare(strict_types=1);

namespace App\Security\Application\User\TwoFactor;

use App\Security\Domain\Model\User\TotpAuthenticator;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\User;

class EffectiveTwoFactorMethod
{
    public function __construct(private readonly TwoFactorPolicy $policy, private readonly TotpAuthenticator $totp) {}

    public function resolve(User $user): ?TwoFactorMethod
    {
        return match ($user->twoFactorMethod()) {
            null => $this->policy->isRequired() ? TwoFactorMethod::Email : null,
            TwoFactorMethod::Email => TwoFactorMethod::Email,
            TwoFactorMethod::Totp => $this->totp->isReadable($user->totpSecret() ?? '') ? TwoFactorMethod::Totp : TwoFactorMethod::Email,
        };
    }

    public function resolveForSso(User $user): ?TwoFactorMethod
    {
        if (!$this->policy->isSsoEnabled()) {
            return null;
        }

        return TwoFactorMethod::Totp === $this->resolve($user) ? TwoFactorMethod::Totp : null;
    }

    public function isFallback(User $user): bool
    {
        return TwoFactorMethod::Totp === $user->twoFactorMethod() && TwoFactorMethod::Email === $this->resolve($user);
    }
}
