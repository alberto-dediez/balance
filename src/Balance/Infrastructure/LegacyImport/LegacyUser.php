<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class LegacyUser
{
    public function __construct(
        public string $id,
        public string $email,
        public bool $globalAdmin,
        public \DateTimeImmutable $createdAt,
    ) {}
}
