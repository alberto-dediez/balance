<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Markdown;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\InvalidChangelogEntryException;
use App\Changelog\Infrastructure\Markdown\ChangelogEntryParser;
use PHPUnit\Framework\TestCase;

class ChangelogEntryParserTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../fixtures/entries';
    }

    public function testACompleteEntryParsesAllFields(): void
    {
        $sut = new ChangelogEntryParser();

        $entry = $sut->parse($this->fixturesPath . '/2026-09-14-complete-entry.md', '2026-09-14-complete-entry.md');

        $this->assertSame('complete-entry', (string) $entry->slug);
        $this->assertSame('Firma electrónica de documentos', $entry->title);
        $this->assertSame('2026-09-14', $entry->date->format('Y-m-d'));
        $this->assertSame(ChangelogCategory::Feature, $entry->category);
        $this->assertSame('1.8.0', $entry->version);
        $this->assertSame('2026-08-20-signature-request', $entry->planSlug);
        $this->assertSame('signature-request.pdf', $entry->manualFileName);
        $this->assertSame('signature-request.tech.md', $entry->technicalManualFileName);
        $this->assertTrue($entry->published);
        $this->assertStringContainsString('Qué cambia', $entry->body);
    }

    public function testOptionalFieldsAreNullWhenAbsent(): void
    {
        $sut = new ChangelogEntryParser();

        $entry = $sut->parse($this->fixturesPath . '/2026-09-01-minimal-entry.md', '2026-09-01-minimal-entry.md');

        $this->assertNull($entry->version);
        $this->assertNull($entry->planSlug);
        $this->assertNull($entry->manualFileName);
        $this->assertNull($entry->technicalManualFileName);
        $this->assertFalse($entry->hasManual());
        $this->assertTrue($entry->published);
    }

    public function testPublishedFalseIsRespected(): void
    {
        $sut = new ChangelogEntryParser();

        $entry = $sut->parse($this->fixturesPath . '/2026-09-02-unpublished-entry.md', '2026-09-02-unpublished-entry.md');

        $this->assertFalse($entry->published);
    }

    public function testDateAsStringAndAsAlreadyParsedYieldTheSameResult(): void
    {
        $sut = new ChangelogEntryParser();

        $entry = $sut->parse($this->fixturesPath . '/2026-09-03-date-as-string.md', '2026-09-03-date-as-string.md');

        $this->assertSame('2026-09-03', $entry->date->format('Y-m-d'));
    }

    public function testTheDateUsesTheSameTimezoneRegardlessOfTheFrontmatterFormat(): void
    {
        $sut = new ChangelogEntryParser();

        $unquoted = $sut->parse($this->fixturesPath . '/2026-09-14-complete-entry.md', '2026-09-14-complete-entry.md');
        $quoted = $sut->parse($this->fixturesPath . '/2026-09-03-date-as-string.md', '2026-09-03-date-as-string.md');

        $this->assertSame($quoted->date->getTimezone()->getName(), $unquoted->date->getTimezone()->getName());
        $this->assertSame((new \DateTimeImmutable('2026-09-14 00:00:00'))->getTimestamp(), $unquoted->date->getTimestamp());
    }

    public function testADateWithoutTimeIsMidnight(): void
    {
        $sut = new ChangelogEntryParser();

        $entry = $sut->parse($this->fixturesPath . '/2026-09-03-date-as-string.md', '2026-09-03-date-as-string.md');

        $this->assertSame('2026-09-03 00:00:00', $entry->date->format('Y-m-d H:i:s'));
    }

    public function testAQuotedDateWithTimeKeepsTheTime(): void
    {
        $sut = new ChangelogEntryParser();

        $entry = $sut->parse($this->fixturesPath . '/2026-09-10-date-with-time.md', '2026-09-10-date-with-time.md');

        $this->assertSame('2026-09-10 14:32:00', $entry->date->format('Y-m-d H:i:s'));
    }

    public function testAnUnquotedDateWithTimeKeepsTheTimeInTheLocalTimezone(): void
    {
        $sut = new ChangelogEntryParser();

        $entry = $sut->parse($this->fixturesPath . '/2026-09-11-date-with-time-unquoted.md', '2026-09-11-date-with-time-unquoted.md');

        $this->assertSame('2026-09-11 09:05:00', $entry->date->format('Y-m-d H:i:s'));
        $this->assertSame((new \DateTimeImmutable('2026-09-11 09:05:00'))->getTimestamp(), $entry->date->getTimestamp());
    }

    public function testSlugIsDerivedByStrippingTheDatePrefix(): void
    {
        $sut = new ChangelogEntryParser();

        $entry = $sut->parse($this->fixturesPath . '/2026-09-01-minimal-entry.md', '2026-09-01-minimal-entry.md');

        $this->assertSame('minimal-entry', (string) $entry->slug);
    }

    /**
     * @dataProvider invalidFixtureProvider
     */
    public function testInvalidFixturesThrow(string $fileName): void
    {
        $sut = new ChangelogEntryParser();

        $this->expectException(InvalidChangelogEntryException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($fileName, '/') . '/');

        $sut->parse($this->fixturesPath . '/' . $fileName, $fileName);
    }

    /**
     * @return iterable<string, list<string>>
     */
    public static function invalidFixtureProvider(): iterable
    {
        yield 'unknown category' => ['2026-09-04-invalid-category.md'];
        yield 'empty title' => ['2026-09-05-empty-title.md'];
        yield 'missing summary' => ['2026-09-06-missing-summary.md'];
        yield 'unparseable date' => ['2026-09-07-unparseable-date.md'];
        yield 'unsafe manual path' => ['2026-09-08-unsafe-manual.md'];
        yield 'no frontmatter' => ['2026-09-09-no-frontmatter.md'];
    }
}
