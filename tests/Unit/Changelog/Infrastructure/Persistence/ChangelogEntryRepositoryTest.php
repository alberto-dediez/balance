<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Persistence;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryFilter;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryRepository;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use PHPUnit\Framework\TestCase;

abstract class ChangelogEntryRepositoryTest extends TestCase
{
    abstract protected function createRepository(): ChangelogEntryRepository;

    /**
     * @param list<ChangelogEntry> $entries
     */
    abstract protected function givenEntries(array $entries): ChangelogEntryRepository;

    public function testEntriesAreOrderedByDateDescending(): void
    {
        $older = $this->entry('older', '2026-01-01');
        $newer = $this->entry('newer', '2026-02-01');

        $repository = $this->givenEntries([$older, $newer]);

        $result = $repository->findByFilter(new ChangelogEntryFilter());

        $this->assertSame('newer', (string) $result[0]->slug);
        $this->assertSame('older', (string) $result[1]->slug);
    }

    public function testEntriesOfTheSameDayAreOrderedByTimeDescending(): void
    {
        $morning = $this->entry('morning', '2026-01-01 09:15');
        $afternoon = $this->entry('afternoon', '2026-01-01 16:40');
        $midnight = $this->entry('midnight', '2026-01-01');

        $repository = $this->givenEntries([$morning, $afternoon, $midnight]);

        $result = $repository->findByFilter(new ChangelogEntryFilter());

        $this->assertSame('afternoon', (string) $result[0]->slug);
        $this->assertSame('morning', (string) $result[1]->slug);
        $this->assertSame('midnight', (string) $result[2]->slug);
    }

    public function testTiesAreBrokenBySlugAscending(): void
    {
        $b = $this->entry('b-slug', '2026-01-01');
        $a = $this->entry('a-slug', '2026-01-01');

        $repository = $this->givenEntries([$b, $a]);

        $result = $repository->findByFilter(new ChangelogEntryFilter());

        $this->assertSame('a-slug', (string) $result[0]->slug);
        $this->assertSame('b-slug', (string) $result[1]->slug);
    }

    public function testPagination(): void
    {
        $entries = [
            $this->entry('e1', '2026-01-01'),
            $this->entry('e2', '2026-01-02'),
            $this->entry('e3', '2026-01-03'),
        ];

        $repository = $this->givenEntries($entries);

        $filter = new ChangelogEntryFilter();
        $filter->page = 0;
        $filter->limit = 2;

        $page1 = $repository->findByFilter($filter);
        $this->assertCount(2, $page1);

        $filter->page = 1;
        $page2 = $repository->findByFilter($filter);
        $this->assertCount(1, $page2);
    }

    public function testTotalResultsCountsTheFilteredTotalNotThePage(): void
    {
        $entries = [
            $this->entry('e1', '2026-01-01'),
            $this->entry('e2', '2026-01-02'),
            $this->entry('e3', '2026-01-03'),
        ];

        $repository = $this->givenEntries($entries);

        $filter = new ChangelogEntryFilter();
        $filter->limit = 2;

        $this->assertSame(3, $repository->totalResultsByFilter($filter));
    }

    public function testFilterByText(): void
    {
        $repository = $this->givenEntries([
            $this->entry('e1', '2026-01-01', title: 'Firma electrónica'),
            $this->entry('e2', '2026-01-02', title: 'Otra cosa'),
        ]);

        $filter = new ChangelogEntryFilter(text: 'firma');

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertSame('e1', (string) $result[0]->slug);
    }

    public function testFilterByCategory(): void
    {
        $repository = $this->givenEntries([
            $this->entry('e1', '2026-01-01', category: ChangelogCategory::Fix),
            $this->entry('e2', '2026-01-02', category: ChangelogCategory::Feature),
        ]);

        $filter = new ChangelogEntryFilter(category: ChangelogCategory::Fix);

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertSame('e1', (string) $result[0]->slug);
    }

    public function testOnlyPublishedExcludesDrafts(): void
    {
        $repository = $this->givenEntries([
            $this->entry('e1', '2026-01-01', published: true),
            $this->entry('e2', '2026-01-02', published: false),
        ]);

        $result = $repository->findByFilter(new ChangelogEntryFilter(onlyPublished: true));

        $this->assertCount(1, $result);
        $this->assertSame('e1', (string) $result[0]->slug);
    }

    public function testFindBySlugExisting(): void
    {
        $repository = $this->givenEntries([$this->entry('e1', '2026-01-01')]);

        $found = $repository->findBySlug(new ChangelogEntrySlug('e1'));

        $this->assertNotNull($found);
        $this->assertSame('e1', (string) $found->slug);
    }

    public function testFindBySlugMissing(): void
    {
        $repository = $this->givenEntries([]);

        $this->assertNull($repository->findBySlug(new ChangelogEntrySlug('missing')));
    }

    public function testFindBySlugReturnsUnpublishedEntriesToo(): void
    {
        $repository = $this->givenEntries([$this->entry('e1', '2026-01-01', published: false)]);

        $found = $repository->findBySlug(new ChangelogEntrySlug('e1'));

        $this->assertNotNull($found);
        $this->assertFalse($found->published);
    }

    private function entry(
        string $slug,
        string $date,
        string $title = 'Título',
        ChangelogCategory $category = ChangelogCategory::Feature,
        bool $published = true,
    ): ChangelogEntry {
        return new ChangelogEntry(
            slug: new ChangelogEntrySlug($slug),
            title: $title,
            date: new \DateTimeImmutable($date),
            category: $category,
            summary: 'Resumen',
            body: 'Cuerpo',
            published: $published,
        );
    }
}
