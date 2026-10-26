<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class LegacyMembership
{
    public const string ROLE_ADMIN = 'organization-admin';

    public const string ROLE_EXTERNAL = 'external';

    public function __construct(
        public string $userId,
        public string $organizationId,
        public string $role,
        public \DateTimeImmutable $createdAt,
    ) {}

    public function isAdmin(): bool
    {
        return self::ROLE_ADMIN === $this->role;
    }
}
