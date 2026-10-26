<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class LegacyEcoProject
{
    public function __construct(
        public string $id,
        public string $displayName,
        public string $slug,
    ) {}
}
