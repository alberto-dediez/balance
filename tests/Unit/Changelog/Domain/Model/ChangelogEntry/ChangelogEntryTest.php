<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryFilter;
use PHPUnit\Framework\TestCase;

class ChangelogEntryTest extends TestCase
{
    public function testHasManualAndHasTechnicalManual(): void
    {
        $withManuals = ChangelogEntryMother::withManuals();
        $withoutManuals = ChangelogEntryMother::any();

        $this->assertTrue($withManuals->hasManual());
        $this->assertTrue($withManuals->hasTechnicalManual());
        $this->assertFalse($withoutManuals->hasManual());
        $this->assertFalse($withoutManuals->hasTechnicalManual());
    }

    public function testPlaceholderIsValid(): void
    {
        $placeholder = ChangelogEntry::placeholder();

        $this->assertSame('placeholder', (string) $placeholder->slug);
    }

    public function testMatchesByTitleText(): void
    {
        $entry = ChangelogEntryMother::any();

        $this->assertTrue($entry->matches(new ChangelogEntryFilter(text: 'TÍTULO')));
        $this->assertFalse($entry->matches(new ChangelogEntryFilter(text: 'no-coincide')));
    }

    public function testMatchesBySummaryText(): void
    {
        $entry = ChangelogEntryMother::any();

        $this->assertTrue($entry->matches(new ChangelogEntryFilter(text: 'resumen')));
    }

    public function testMatchesByCategory(): void
    {
        $entry = ChangelogEntryMother::withCategory(ChangelogCategory::Fix);

        $this->assertTrue($entry->matches(new ChangelogEntryFilter(category: ChangelogCategory::Fix)));
        $this->assertFalse($entry->matches(new ChangelogEntryFilter(category: ChangelogCategory::Feature)));
    }

    public function testUnpublishedEntryIsExcludedWhenOnlyPublished(): void
    {
        $entry = ChangelogEntryMother::unpublished();

        $this->assertFalse($entry->matches(new ChangelogEntryFilter(onlyPublished: true)));
        $this->assertTrue($entry->matches(new ChangelogEntryFilter(onlyPublished: false)));
    }
}
