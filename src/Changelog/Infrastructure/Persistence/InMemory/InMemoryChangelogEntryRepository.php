<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Persistence\InMemory;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryFilter;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryRepository;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;

class InMemoryChangelogEntryRepository implements ChangelogEntryRepository
{
    /** @var list<ChangelogEntry> */
    private array $entries = [];

    public function add(ChangelogEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    public function clear(): void
    {
        $this->entries = [];
    }

    public function remove(ChangelogEntrySlug $slug): void
    {
        $this->entries = array_values(array_filter(
            $this->entries,
            static fn(ChangelogEntry $entry): bool => !$entry->slug->equals($slug),
        ));
    }

    public function findBySlug(ChangelogEntrySlug $slug): ?ChangelogEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->slug->equals($slug)) {
                return $entry;
            }
        }

        return null;
    }

    public function findByFilter(ChangelogEntryFilter $filter): array
    {
        $entries = $this->sorted($this->matching($filter));

        return array_slice($entries, $filter->page * $filter->limit, $filter->limit);
    }

    public function totalResultsByFilter(ChangelogEntryFilter $filter): int
    {
        return count($this->matching($filter));
    }

    /**
     * @return list<ChangelogEntry>
     */
    private function matching(ChangelogEntryFilter $filter): array
    {
        return array_values(array_filter($this->entries, static fn(ChangelogEntry $entry): bool => $entry->matches($filter)));
    }

    /**
     * @param list<ChangelogEntry> $entries
     *
     * @return list<ChangelogEntry>
     */
    private function sorted(array $entries): array
    {
        usort($entries, static function (ChangelogEntry $a, ChangelogEntry $b): int {
            $byDate = $b->date <=> $a->date;

            if (0 !== $byDate) {
                return $byDate;
            }

            return (string) $a->slug <=> (string) $b->slug;
        });

        return $entries;
    }
}
