<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\ChangelogEntry;

final class ChangelogEntry
{
    public function __construct(
        public readonly ChangelogEntrySlug $slug,
        public readonly string $title,
        public readonly \DateTimeImmutable $date,
        public readonly ChangelogCategory $category,
        public readonly string $summary,
        public readonly string $body,
        public readonly ?string $version = null,
        public readonly ?string $planSlug = null,
        public readonly ?string $manualFileName = null,
        public readonly ?string $technicalManualFileName = null,
        public readonly bool $published = true,
    ) {}

    public static function placeholder(): self
    {
        return new self(
            slug: new ChangelogEntrySlug('placeholder'),
            title: '',
            date: new \DateTimeImmutable('NOW'),
            category: ChangelogCategory::Improvement,
            summary: '',
            body: '',
        );
    }

    public function hasManual(): bool
    {
        return null !== $this->manualFileName;
    }

    public function hasTechnicalManual(): bool
    {
        return null !== $this->technicalManualFileName;
    }

    public function matches(ChangelogEntryFilter $filter): bool
    {
        if ($filter->onlyPublished && !$this->published) {
            return false;
        }

        if (null !== $filter->category && $this->category !== $filter->category) {
            return false;
        }

        if (null !== $filter->text && '' !== $filter->text) {
            $needle = mb_strtolower($filter->text);

            if (!str_contains(mb_strtolower($this->title), $needle) && !str_contains(mb_strtolower($this->summary), $needle)) {
                return false;
            }
        }

        return true;
    }
}
