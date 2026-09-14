<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;

class ChangelogEntryMother
{
    public static function any(): ChangelogEntry
    {
        return self::withSlug('changelog-entry');
    }

    public static function withSlug(string $slug): ChangelogEntry
    {
        return new ChangelogEntry(
            slug: new ChangelogEntrySlug($slug),
            title: 'Título de prueba',
            date: new \DateTimeImmutable('2026-09-14'),
            category: ChangelogCategory::Feature,
            summary: 'Resumen de prueba',
            body: 'Cuerpo de prueba',
        );
    }

    public static function withDate(string $date): ChangelogEntry
    {
        return new ChangelogEntry(
            slug: new ChangelogEntrySlug('changelog-entry'),
            title: 'Título de prueba',
            date: new \DateTimeImmutable($date),
            category: ChangelogCategory::Feature,
            summary: 'Resumen de prueba',
            body: 'Cuerpo de prueba',
        );
    }

    public static function withCategory(ChangelogCategory $category): ChangelogEntry
    {
        return new ChangelogEntry(
            slug: new ChangelogEntrySlug('changelog-entry'),
            title: 'Título de prueba',
            date: new \DateTimeImmutable('2026-09-14'),
            category: $category,
            summary: 'Resumen de prueba',
            body: 'Cuerpo de prueba',
        );
    }

    public static function unpublished(): ChangelogEntry
    {
        return new ChangelogEntry(
            slug: new ChangelogEntrySlug('changelog-entry'),
            title: 'Título de prueba',
            date: new \DateTimeImmutable('2026-09-14'),
            category: ChangelogCategory::Feature,
            summary: 'Resumen de prueba',
            body: 'Cuerpo de prueba',
            published: false,
        );
    }

    public static function withManuals(): ChangelogEntry
    {
        return new ChangelogEntry(
            slug: new ChangelogEntrySlug('changelog-entry'),
            title: 'Título de prueba',
            date: new \DateTimeImmutable('2026-09-14'),
            category: ChangelogCategory::Feature,
            summary: 'Resumen de prueba',
            body: 'Cuerpo de prueba',
            manualFileName: 'changelog-entry.pdf',
            technicalManualFileName: 'changelog-entry.tech.md',
        );
    }
}
