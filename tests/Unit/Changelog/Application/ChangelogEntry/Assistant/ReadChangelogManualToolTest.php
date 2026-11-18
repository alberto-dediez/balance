<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry\Assistant;

use App\Changelog\Application\ChangelogEntry\Assistant\ReadChangelogManualTool;
use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\DownloadManual\DownloadManualService;
use App\Changelog\Infrastructure\Storage\InMemoryManualStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Tests\Unit\Changelog\Application\ChangelogEntry\ChangelogEntryRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ReadChangelogManualToolTest extends TestCase
{
    private InMemoryManualStorage $storage;

    private ReadChangelogManualTool $sut;

    protected function setUp(): void
    {
        $this->storage = new InMemoryManualStorage();
        $authorizer = new ChangelogEntryAuthorizer();
        $this->sut = new ReadChangelogManualTool(
            new DownloadManualService(ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::withManuals(), ChangelogEntryMother::withSlug('sin-manual')), $this->storage, $authorizer),
            $authorizer,
        );
    }

    public function testAttachesThePdfOfTheManual(): void
    {
        $this->storage->add('changelog-entry.pdf', '%PDF-1.4 contenido');

        $result = $this->sut->execute($this->arguments('changelog-entry'), UserMother::adminUser(), $this->context());

        $this->assertSame('changelog-entry', $result->data['slug']);
        $this->assertSame('changelog-entry.pdf', $result->data['name']);
        $this->assertSame('application/pdf', $result->data['mimeType']);
        $this->assertTrue($result->data['attached']);
        $this->assertCount(1, $result->attachments);
        $this->assertSame('application/pdf', $result->attachments[0]->mimeType);
        $this->assertSame('%PDF-1.4 contenido', $result->attachments[0]->contents);
        $this->assertSame('admin_changelog_entry', $result->link?->route);
    }

    public function testRejectsAManualAboveTheSizeLimit(): void
    {
        $this->storage->add('changelog-entry.pdf', str_repeat('a', 11));

        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('supera el tamaño máximo');

        $this->sut->execute($this->arguments('changelog-entry'), UserMother::adminUser(), new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'), 10));
    }

    public function testAnEntryWithoutManualFails(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('La novedad no tiene manual de usuario.');

        $this->sut->execute($this->arguments('sin-manual'), UserMother::adminUser(), $this->context());
    }

    public function testAnUnknownEntryFails(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('La novedad no existe; búscala con list_changelog_entries.');

        $this->sut->execute($this->arguments('no-existe'), UserMother::adminUser(), $this->context());
    }

    public function testRejectsAnInvalidSlug(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        $this->sut->execute($this->arguments('Manual Usuario'), UserMother::adminUser(), $this->context());
    }

    public function testIsAReadToolAvailableToAdmins(): void
    {
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::ownerUser()));

        $this->expectException(UnauthorizedActionException::class);

        $this->sut->execute($this->arguments('changelog-entry'), UserMother::ownerUser(), $this->context());
    }

    private function arguments(string $slug): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), ['slug' => $slug], $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
