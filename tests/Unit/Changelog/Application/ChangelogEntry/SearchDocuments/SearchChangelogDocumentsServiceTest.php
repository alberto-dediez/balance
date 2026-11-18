<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry\SearchDocuments;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\SearchDocuments\ChangelogDocumentMatch;
use App\Changelog\Application\ChangelogEntry\SearchDocuments\SearchChangelogDocumentsRequest;
use App\Changelog\Application\ChangelogEntry\SearchDocuments\SearchChangelogDocumentsService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Infrastructure\Storage\InMemoryManualStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Changelog\Application\ChangelogEntry\ChangelogEntryRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class SearchChangelogDocumentsServiceTest extends TestCase
{
    private InMemoryManualStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new InMemoryManualStorage();
        $this->storage->add('changelog-entry.tech.md', '# Manual técnico con la palabra Ficheros dentro');
    }

    public function testSearchesTheBodyIgnoringCase(): void
    {
        $sut = $this->createSUT(ChangelogEntryMother::withManuals());

        $response = $sut(new SearchChangelogDocumentsRequest('CUERPO'), UserMother::adminUser());

        $this->assertSame(1, $response->total);
        $this->assertSame(ChangelogDocumentMatch::SOURCE_ENTRY, $response->matches[0]->source);
        $this->assertSame('Cuerpo de prueba', $response->matches[0]->excerpt);
        $this->assertSame('changelog-entry', (string) $response->matches[0]->entry->slug);
    }

    public function testTechnicalManualsOnlyForSuperAdmins(): void
    {
        $sut = $this->createSUT(ChangelogEntryMother::withManuals());

        $asAdmin = $sut(new SearchChangelogDocumentsRequest('ficheros'), UserMother::adminUser());
        $asSuperAdmin = $sut(new SearchChangelogDocumentsRequest('ficheros'), UserMother::superAdminUser());

        $this->assertSame(0, $asAdmin->total);
        $this->assertSame(1, $asSuperAdmin->total);
        $this->assertSame(ChangelogDocumentMatch::SOURCE_TECHNICAL_MANUAL, $asSuperAdmin->matches[0]->source);
    }

    public function testShortTextReturnsNothing(): void
    {
        $response = $this->createSUT(ChangelogEntryMother::withManuals())(new SearchChangelogDocumentsRequest('cu'), UserMother::superAdminUser());

        $this->assertSame([], $response->matches);
        $this->assertSame(0, $response->total);
    }

    public function testLimitsTheMatchesButCountsTheTotal(): void
    {
        $sut = $this->createSUT(
            ChangelogEntryMother::withSlug('primera'),
            ChangelogEntryMother::withSlug('segunda'),
            ChangelogEntryMother::withSlug('tercera'),
        );

        $response = $sut(new SearchChangelogDocumentsRequest('cuerpo', 2), UserMother::adminUser());

        $this->assertCount(2, $response->matches);
        $this->assertSame(3, $response->total);
    }

    public function testNeverSearchesUnpublishedEntries(): void
    {
        $response = $this->createSUT(ChangelogEntryMother::unpublished())(new SearchChangelogDocumentsRequest('cuerpo'), UserMother::adminUser());

        $this->assertSame(0, $response->total);
    }

    public function testTheExcerptSurroundsTheFirstOccurrence(): void
    {
        $entry = new ChangelogEntry(new ChangelogEntrySlug('larga'), 'Larga', new \DateTimeImmutable('2026-09-14'), ChangelogCategory::Feature, 'Resumen', str_repeat('a', 400) . 'aguja' . str_repeat('b', 400));

        $response = $this->createSUT($entry)(new SearchChangelogDocumentsRequest('aguja'), UserMother::adminUser());

        $this->assertSame(150 + 5 + 150, mb_strlen($response->matches[0]->excerpt));
        $this->assertStringContainsString('aguja', $response->matches[0]->excerpt);
    }

    public function testAnOwnerCannotSearch(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT(ChangelogEntryMother::any())(new SearchChangelogDocumentsRequest('cuerpo'), UserMother::ownerUser());
    }

    private function createSUT(ChangelogEntry ...$entries): SearchChangelogDocumentsService
    {
        return new SearchChangelogDocumentsService(ChangelogEntryRepositoryMother::withEntries(...$entries), $this->storage, new ChangelogEntryAuthorizer());
    }
}
