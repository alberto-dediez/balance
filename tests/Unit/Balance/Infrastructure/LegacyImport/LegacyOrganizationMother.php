<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\LegacyOrganization;

class LegacyOrganizationMother
{
    public static function from(
        string $slug,
        string $type = 'hotel',
        ?string $displayName = null,
        ?string $id = null,
        bool $active = true,
    ): LegacyOrganization {
        return new LegacyOrganization(
            id: $id ?? 'org-' . $slug,
            slug: $slug,
            displayName: $displayName ?? ucfirst($slug),
            type: $type,
            active: $active,
            createdAt: new \DateTimeImmutable('2024-08-29 14:10:45'),
        );
    }

    public static function ota(string $slug, ?string $displayName = null): LegacyOrganization
    {
        return self::from($slug, 'ota', $displayName);
    }

    public static function hotel(string $slug, ?string $displayName = null): LegacyOrganization
    {
        return self::from($slug, 'hotel', $displayName);
    }
}
