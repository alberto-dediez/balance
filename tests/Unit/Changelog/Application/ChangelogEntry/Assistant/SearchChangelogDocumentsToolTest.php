<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry\Assistant;

use App\Changelog\Application\ChangelogEntry\Assistant\SearchChangelogDocumentsTool;
use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\SearchDocuments\SearchChangelogDocumentsService;
use App\Changelog\Infrastructure\Storage\InMemoryManualStorage;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Tests\Unit\Changelog\Application\ChangelogEntry\ChangelogEntryRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class SearchChangelogDocumentsToolTest extends TestCase
{
    private SearchChangelogDocumentsTool $sut;

    protected function setUp(): void
    {
        $storage = new InMemoryManualStorage();
        $storage->add('changelog-entry.tech.md', 'Detalle técnico del módulo');
        $authorizer = new ChangelogEntryAuthorizer();
        $this->sut = new SearchChangelogDocumentsTool(
            new SearchChangelogDocumentsService(ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::withManuals()), $storage, $authorizer),
            $authorizer,
        );
    }

    public function testReturnsTheMatchesOfTheBody(): void
    {
        $result = $this->sut->execute($this->arguments(['text' => 'cuerpo']), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame([[
            'slug' => 'changelog-entry',
            'href' => 'route:admin_changelog_entry?slug=changelog-entry',
            'title' => 'Título de prueba',
            'date' => '2026-09-14',
            'source' => 'entry',
            'excerpt' => 'Cuerpo de prueba',
        ]], $result->data['matches']);
    }

    public function testIncludesTheTechnicalManualForSuperAdmins(): void
    {
        $result = $this->sut->execute($this->arguments(['text' => 'módulo']), UserMother::superAdminUser(), $this->context());

        $this->assertSame('technical_manual', $result->data['matches'][0]['source']);
    }

    public function testIsAReadToolAvailableToAdmins(): void
    {
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::ownerUser()));
    }

    /** @param array<string, mixed> $raw */
    private function arguments(array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
