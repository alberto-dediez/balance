<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

enum ApiTokenStatus: string
{
    case Active = 'Active';
    case Expired = 'Expired';
    case Revoked = 'Revoked';

    public function style(): string
    {
        return match ($this) {
            self::Active => 'bg-success-subtle text-success',
            self::Expired => 'bg-warning-subtle text-warning',
            self::Revoked => 'bg-danger-subtle text-danger',
        };
    }
}
