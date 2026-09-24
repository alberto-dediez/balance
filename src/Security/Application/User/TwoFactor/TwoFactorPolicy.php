<?php

declare(strict_types=1);

namespace App\Security\Application\User\TwoFactor;

interface TwoFactorPolicy
{
    public function isRequired(): bool;

    public function isSsoEnabled(): bool;
}
