<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\SearchDocuments;

class SearchChangelogDocumentsRequest
{
    public function __construct(
        public readonly string $text,
        public readonly int $limit = 10,
    ) {}
}
