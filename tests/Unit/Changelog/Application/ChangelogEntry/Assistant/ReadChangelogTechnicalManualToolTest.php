<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry\Assistant;

use App\Changelog\Application\ChangelogEntry\Assistant\ReadChangelogTechnicalManualTool;
use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\ViewTechnicalManual\ViewTechnicalManualService;
use App\Changelog\Infrastructure\Storage\InMemoryManualStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolResult;
use App\Tests\Unit\Changelog\Application\ChangelogEntry\ChangelogEntryRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ReadChangelogTechnicalManualToolTest extends TestCase
{
    private InMemoryManualStorage $storage;

    private ReadChangelogTechnicalManualTool $sut;

    protected function setUp(): void
    {
        $this->storage = new InMemoryManualStorage();
        $authorizer = new ChangelogEntryAuthorizer();
        $this->sut = new ReadChangelogTechnicalManualTool(
            new ViewTechnicalManualService(ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::withManuals(), ChangelogEntryMother::withSlug('sin-manual')), $this->storage, $authorizer),
            $authorizer,
        );
    }

    public function testReturnsTheMarkdownOfTheTechnicalManual(): void
    {
        $this->storage->add('changelog-entry.tech.md', '# Manual técnico');

        $result = $this->sut->execute($this->arguments('changelog-entry'), UserMother::superAdminUser(), $this->context());

        $this->assertSame(['slug' => 'changelog-entry', 'markdown' => '# Manual técnico', 'truncated' => false], $result->data);
        $this->assertSame('admin_changelog_tech_manual', $result->link?->route);
        $this->assertSame(['slug'], $result->link->parameters);
    }

    public function testTruncatesALongManual(): void
    {
        $this->storage->add('changelog-entry.tech.md', str_repeat('a', ToolResult::TEXT_MAX_LENGTH + 1));

        $result = $this->sut->execute($this->arguments('changelog-entry'), UserMother::superAdminUser(), $this->context());

        $this->assertTrue($result->data['truncated']);
        $this->assertSame(ToolResult::TEXT_MAX_LENGTH, mb_strlen($result->data['markdown']));
    }

    public function testAnEntryWithoutTechnicalManualFails(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('La novedad no tiene manual técnico.');

        $this->sut->execute($this->arguments('sin-manual'), UserMother::superAdminUser(), $this->context());
    }

    public function testAnUnknownEntryFails(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('La novedad no existe; búscala con list_changelog_entries.');

        $this->sut->execute($this->arguments('no-existe'), UserMother::superAdminUser(), $this->context());
    }

    public function testRejectsAnInvalidSlug(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        $this->sut->execute($this->arguments('Manual Técnico'), UserMother::superAdminUser(), $this->context());
    }

    public function testIsAvailableOnlyToSuperAdmins(): void
    {
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::superAdminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::ownerUser()));

        $this->expectException(UnauthorizedActionException::class);

        $this->sut->execute($this->arguments('changelog-entry'), UserMother::adminUser(), $this->context());
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
