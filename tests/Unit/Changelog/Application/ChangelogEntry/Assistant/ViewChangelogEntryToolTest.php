<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry\Assistant;

use App\Changelog\Application\ChangelogEntry\Assistant\ViewChangelogEntryTool;
use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\View\ViewChangelogEntryService;
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

class ViewChangelogEntryToolTest extends TestCase
{
    public function testReturnsTheEntryWithItsBody(): void
    {
        $sut = $this->createSUT(ChangelogEntryMother::withManuals());

        $result = $sut->execute($this->arguments($sut, 'changelog-entry'), UserMother::adminUser(), $this->context());

        $this->assertSame('Título de prueba', $result->data['title']);
        $this->assertSame('2026-09-14', $result->data['date']);
        $this->assertSame('feature', $result->data['category']);
        $this->assertSame('Cuerpo de prueba', $result->data['body']);
        $this->assertFalse($result->data['truncated']);
        $this->assertTrue($result->data['hasManual']);
        $this->assertTrue($result->data['hasTechnicalManual']);
        $this->assertSame(['slug' => 'changelog-entry'], $result->references);
        $this->assertSame('admin_changelog_entry', $result->link?->route);
        $this->assertSame(['slug'], $result->link->parameters);
    }

    public function testAnUnpublishedEntryIsNotFound(): void
    {
        $sut = $this->createSUT(ChangelogEntryMother::unpublished());

        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('La novedad no existe; búscala con list_changelog_entries.');

        $sut->execute($this->arguments($sut, 'changelog-entry'), UserMother::adminUser(), $this->context());
    }

    public function testRejectsAnInvalidSlug(): void
    {
        $sut = $this->createSUT();

        $this->expectException(InvalidToolArgumentsException::class);

        $sut->execute($this->arguments($sut, '../Secreto'), UserMother::adminUser(), $this->context());
    }

    public function testIsAReadToolAvailableOnlyToAdmins(): void
    {
        $sut = $this->createSUT();

        $this->assertSame(ToolMode::Read, $sut->mode());
        $this->assertTrue($sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($sut->isAvailableTo(UserMother::ownerUser()));
    }

    private function createSUT(ChangelogEntry ...$entries): ViewChangelogEntryTool
    {
        $authorizer = new ChangelogEntryAuthorizer();

        return new ViewChangelogEntryTool(new ViewChangelogEntryService(ChangelogEntryRepositoryMother::withEntries(...$entries), $authorizer), $authorizer);
    }

    private function arguments(ViewChangelogEntryTool $sut, string $slug): ToolArguments
    {
        return ToolArgumentsFactory::create($sut->parameters(), ['slug' => $slug], $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
