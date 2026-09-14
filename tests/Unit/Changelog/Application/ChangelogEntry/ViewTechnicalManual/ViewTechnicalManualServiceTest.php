<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry\ViewTechnicalManual;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\ViewTechnicalManual\ViewTechnicalManualRequest;
use App\Changelog\Application\ChangelogEntry\ViewTechnicalManual\ViewTechnicalManualService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Changelog\Infrastructure\Storage\InMemoryManualStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Changelog\Application\ChangelogEntry\ChangelogEntryRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ViewTechnicalManualServiceTest extends TestCase
{
    public function testItReturnsTheTechnicalManualMarkdown(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::withManuals());

        $storage = new InMemoryManualStorage();
        $storage->add('changelog-entry.tech.md', '# Manual técnico');

        $sut = new ViewTechnicalManualService($repository, $storage, new ChangelogEntryAuthorizer());

        $response = $sut(new ViewTechnicalManualRequest(new ChangelogEntrySlug('changelog-entry')), UserMother::superAdminUser());

        $this->assertSame('# Manual técnico', $response->markdown);
    }

    public function testEntryWithoutTechnicalManualFails(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::any());

        $sut = new ViewTechnicalManualService($repository, new InMemoryManualStorage(), new ChangelogEntryAuthorizer());

        $this->expectException(ManualNotFoundException::class);

        $sut(new ViewTechnicalManualRequest(new ChangelogEntrySlug('changelog-entry')), UserMother::superAdminUser());
    }

    public function testAnOwnerCannotViewTheTechnicalManual(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::withManuals());

        $sut = new ViewTechnicalManualService($repository, new InMemoryManualStorage(), new ChangelogEntryAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ViewTechnicalManualRequest(new ChangelogEntrySlug('changelog-entry')), UserMother::ownerUser());
    }

    public function testAnAdminCannotViewTheTechnicalManual(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::withManuals());

        $sut = new ViewTechnicalManualService($repository, new InMemoryManualStorage(), new ChangelogEntryAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ViewTechnicalManualRequest(new ChangelogEntrySlug('changelog-entry')), UserMother::adminUser());
    }
}
