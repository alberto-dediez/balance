<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry\Assistant;

use App\Changelog\Application\ChangelogEntry\Assistant\ListChangelogEntriesTool;
use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\List\ListChangelogEntriesService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Tests\Unit\Changelog\Application\ChangelogEntry\ChangelogEntryRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListChangelogEntriesToolTest extends TestCase
{
    public function testListsThePublishedEntries(): void
    {
        $sut = $this->createSUT(ChangelogEntryMother::withManuals());

        $result = $sut->execute($this->arguments($sut, []), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame([[
            'slug' => 'changelog-entry',
            'href' => 'route:admin_changelog_entry?slug=changelog-entry',
            'title' => 'Título de prueba',
            'date' => '2026-09-14',
            'category' => 'feature',
            'summary' => 'Resumen de prueba',
            'hasManual' => true,
            'hasTechnicalManual' => true,
        ]], $result->data['entries']);
        $this->assertSame('1 novedades encontradas', $result->summary);
    }

    public function testNeverReturnsUnpublishedEntries(): void
    {
        $sut = $this->createSUT(ChangelogEntryMother::unpublished(), ChangelogEntryMother::withSlug('publicada'));

        $result = $sut->execute($this->arguments($sut, ['text' => 'prueba']), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame(['publicada'], array_column($result->data['entries'], 'slug'));
    }

    public function testFiltersByCategoryAndLimits(): void
    {
        $sut = $this->createSUT(
            ChangelogEntryMother::withSlug('primera'),
            ChangelogEntryMother::withSlug('segunda'),
            new ChangelogEntry(ChangelogEntryMother::withSlug('fix')->slug, 'Corrección', new \DateTimeImmutable('2026-09-15'), ChangelogCategory::Fix, 'Resumen', 'Cuerpo'),
        );

        $features = $sut->execute($this->arguments($sut, ['category' => 'feature', 'limit' => 1]), UserMother::adminUser(), $this->context());

        $this->assertSame(2, $features->data['total']);
        $this->assertCount(1, $features->data['entries']);
    }

    public function testRejectsAnUnknownCategory(): void
    {
        $sut = $this->createSUT();

        $this->expectException(InvalidToolArgumentsException::class);

        $this->arguments($sut, ['category' => 'unknown']);
    }

    public function testIsAReadToolAvailableOnlyToAdmins(): void
    {
        $sut = $this->createSUT();

        $this->assertSame(ToolMode::Read, $sut->mode());
        $this->assertTrue($sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($sut->isAvailableTo(UserMother::ownerUser()));
    }

    private function createSUT(ChangelogEntry ...$entries): ListChangelogEntriesTool
    {
        $authorizer = new ChangelogEntryAuthorizer();

        return new ListChangelogEntriesTool(new ListChangelogEntriesService(ChangelogEntryRepositoryMother::withEntries(...$entries), $authorizer), $authorizer);
    }

    /** @param array<string, mixed> $raw */
    private function arguments(ListChangelogEntriesTool $sut, array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
