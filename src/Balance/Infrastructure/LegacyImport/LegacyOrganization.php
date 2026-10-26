<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class LegacyOrganization
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $displayName,
        public string $type,
        public bool $active,
        public \DateTimeImmutable $createdAt,
    ) {}

    public function isOta(): bool
    {
        return 'ota' === $this->type;
    }
}
