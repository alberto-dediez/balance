<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class RedirectRule
{
    public function __construct(
        public string $slug,
        public string $type,
        public ?string $targetUrl,
        public string $comment,
        public bool $skipped = false,
    ) {}
}
