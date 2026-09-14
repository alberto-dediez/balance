<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\List;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;

class ListChangelogEntriesResponse
{
    /**
     * @param list<ChangelogEntry> $entries
     */
    public function __construct(
        public readonly array $entries,
        public readonly int $totalResults,
    ) {}
}
