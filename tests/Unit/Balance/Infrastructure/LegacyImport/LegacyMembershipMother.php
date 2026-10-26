<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\LegacyMembership;

class LegacyMembershipMother
{
    public static function from(
        string $userId,
        string $organizationId,
        string $role = LegacyMembership::ROLE_ADMIN,
        string $createdAt = '2024-09-01 10:00:00',
    ): LegacyMembership {
        return new LegacyMembership(
            userId: $userId,
            organizationId: $organizationId,
            role: $role,
            createdAt: new \DateTimeImmutable($createdAt),
        );
    }
}
