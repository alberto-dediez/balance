<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\SearchDocuments;

class SearchChangelogDocumentsResponse
{
    /**
     * @param list<ChangelogDocumentMatch> $matches
     */
    public function __construct(
        public readonly array $matches = [],
        public readonly int $total = 0,
    ) {}
}
