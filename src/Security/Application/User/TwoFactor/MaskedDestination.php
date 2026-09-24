<?php

declare(strict_types=1);

namespace App\Security\Application\User\TwoFactor;

final class MaskedDestination
{
    public static function email(string $email): string
    {
        $parts = explode('@', $email, 2);
        $local = $parts[0];
        $domain = $parts[1] ?? '';
        $masked = substr($local, 0, 1) . str_repeat('*', max(0, strlen($local) - 1));

        return $masked . '@' . $domain;
    }
}
