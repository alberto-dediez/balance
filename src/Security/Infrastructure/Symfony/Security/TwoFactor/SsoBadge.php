<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security\TwoFactor;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

final class SsoBadge implements BadgeInterface
{
    public function isResolved(): bool
    {
        return true;
    }
}
