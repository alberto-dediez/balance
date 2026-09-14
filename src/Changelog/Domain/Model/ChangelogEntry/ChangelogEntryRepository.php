<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\ChangelogEntry;

interface ChangelogEntryRepository
{
    public function findBySlug(ChangelogEntrySlug $slug): ?ChangelogEntry;

    /** @return list<ChangelogEntry> */
    public function findByFilter(ChangelogEntryFilter $filter): array;

    public function totalResultsByFilter(ChangelogEntryFilter $filter): int;
}
