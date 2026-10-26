<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\LegacyUser;

class LegacyUserMother
{
    public static function from(string $email, bool $globalAdmin = false, ?string $id = null): LegacyUser
    {
        return new LegacyUser(
            id: $id ?? 'user-' . $email,
            email: $email,
            globalAdmin: $globalAdmin,
            createdAt: new \DateTimeImmutable('2024-08-29 14:10:33'),
        );
    }
}
