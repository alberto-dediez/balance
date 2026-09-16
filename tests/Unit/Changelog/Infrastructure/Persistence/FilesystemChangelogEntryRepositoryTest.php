<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Persistence;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryFilter;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryRepository;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Infrastructure\Markdown\ChangelogEntryParser;
use App\Changelog\Infrastructure\Persistence\Filesystem\FilesystemChangelogEntryRepository;

class FilesystemChangelogEntryRepositoryTest extends ChangelogEntryRepositoryTest
{
    private string $entriesPath;

    protected function setUp(): void
    {
        $this->entriesPath = sys_get_temp_dir() . '/changelog-fs-repo-test-' . uniqid();
        mkdir($this->entriesPath, 0o775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->entriesPath . '/*.md') ?: [] as $file) {
            unlink($file);
        }

        @rmdir($this->entriesPath);
    }

    protected function createRepository(): ChangelogEntryRepository
    {
        return new FilesystemChangelogEntryRepository($this->entriesPath, new ChangelogEntryParser());
    }

    protected function givenEntries(array $entries): ChangelogEntryRepository
    {
        foreach ($entries as $entry) {
            $this->writeEntry($entry);
        }

        return $this->createRepository();
    }

    public function testAMissingDirectoryYieldsAnEmptyList(): void
    {
        $repository = new FilesystemChangelogEntryRepository($this->entriesPath . '-missing', new ChangelogEntryParser());

        $this->assertSame([], $repository->findByFilter(new ChangelogEntryFilter()));
        $this->assertSame(0, $repository->totalResultsByFilter(new ChangelogEntryFilter()));
    }

    public function testAnInvalidFileIsIgnoredWithoutBreakingTheRest(): void
    {
        file_put_contents($this->entriesPath . '/2026-01-01-invalid.md', 'no frontmatter here');

        $repository = $this->givenEntries([
            new ChangelogEntry(
                slug: new ChangelogEntrySlug('valid'),
                title: 'Válida',
                date: new \DateTimeImmutable('2026-01-02'),
                category: ChangelogCategory::Fix,
                summary: 'Resumen',
                body: 'Cuerpo',
            ),
        ]);

        $result = $repository->findByFilter(new ChangelogEntryFilter());

        $this->assertCount(1, $result);
        $this->assertSame('valid', (string) $result[0]->slug);
    }

    public function testTwoFilesWithTheSameSlugAndDayKeepTheLatestTime(): void
    {
        file_put_contents($this->entriesPath . '/2026-01-01-dup.md', $this->frontMatter('"2026-01-01 18:00"', 'Reciente'));
        file_put_contents($this->entriesPath . '/2026-01-02-dup.md', $this->frontMatter('2026-01-01', 'Antigua'));

        $repository = $this->createRepository();

        $found = $repository->findBySlug(new ChangelogEntrySlug('dup'));

        $this->assertNotNull($found);
        $this->assertSame('Reciente', $found->title);
    }

    public function testTwoFilesWithTheSameSlugKeepTheLatestDate(): void
    {
        file_put_contents($this->entriesPath . '/2026-01-01-dup.md', $this->frontMatter('2026-01-01', 'Antigua'));
        file_put_contents($this->entriesPath . '/2026-02-01-dup.md', $this->frontMatter('2026-02-01', 'Reciente'));

        $repository = $this->createRepository();

        $found = $repository->findBySlug(new ChangelogEntrySlug('dup'));

        $this->assertNotNull($found);
        $this->assertSame('Reciente', $found->title);
    }

    private function writeEntry(ChangelogEntry $entry): void
    {
        $fileName = sprintf('%s-%s.md', $entry->date->format('Y-m-d'), (string) $entry->slug);

        file_put_contents(
            $this->entriesPath . '/' . $fileName,
            $this->frontMatterFromEntry($entry),
        );
    }

    private function frontMatterFromEntry(ChangelogEntry $entry): string
    {
        return sprintf(
            "---\ntitle: %s\ndate: \"%s\"\ncategory: %s\nsummary: %s\npublished: %s\n---\n\n%s\n",
            $entry->title,
            $entry->date->format('Y-m-d H:i'),
            $entry->category->value,
            $entry->summary,
            $entry->published ? 'true' : 'false',
            $entry->body,
        );
    }

    private function frontMatter(string $date, string $title): string
    {
        return sprintf(
            "---\ntitle: %s\ndate: %s\ncategory: fix\nsummary: Resumen\n---\n\nCuerpo\n",
            $title,
            $date,
        );
    }
}
