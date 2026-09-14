<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Persistence\Filesystem;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryFilter;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryRepository;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Domain\Model\ChangelogEntry\InvalidChangelogEntryException;
use App\Changelog\Infrastructure\Markdown\ChangelogEntryParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class FilesystemChangelogEntryRepository implements ChangelogEntryRepository
{
    public function __construct(
        private readonly string $entriesPath,
        private readonly ChangelogEntryParser $parser,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function findBySlug(ChangelogEntrySlug $slug): ?ChangelogEntry
    {
        foreach ($this->all() as $entry) {
            if ($entry->slug->equals($slug)) {
                return $entry;
            }
        }

        return null;
    }

    public function findByFilter(ChangelogEntryFilter $filter): array
    {
        $entries = array_values(array_filter($this->all(), static fn(ChangelogEntry $entry): bool => $entry->matches($filter)));

        return array_slice($entries, $filter->page * $filter->limit, $filter->limit);
    }

    public function totalResultsByFilter(ChangelogEntryFilter $filter): int
    {
        return count(array_filter($this->all(), static fn(ChangelogEntry $entry): bool => $entry->matches($filter)));
    }

    /**
     * @return list<ChangelogEntry>
     */
    private function all(): array
    {
        /** @var array<string, ChangelogEntry> $bySlug */
        $bySlug = [];

        foreach (glob($this->entriesPath . '/*.md') ?: [] as $absolutePath) {
            $fileName = basename($absolutePath);

            try {
                $entry = $this->parser->parse($absolutePath, $fileName);
            } catch (InvalidChangelogEntryException $exception) {
                $this->logger?->warning($exception->getMessage());

                continue;
            }

            $slug = (string) $entry->slug;

            if (isset($bySlug[$slug]) && $bySlug[$slug]->date >= $entry->date) {
                $this->logger?->warning(sprintf('Duplicate changelog entry slug "%s" in file "%s"', $slug, $fileName));

                continue;
            }

            $bySlug[$slug] = $entry;
        }

        $entries = array_values($bySlug);

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
