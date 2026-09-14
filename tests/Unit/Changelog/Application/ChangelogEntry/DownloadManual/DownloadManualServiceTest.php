<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry\DownloadManual;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\DownloadManual\DownloadManualRequest;
use App\Changelog\Application\ChangelogEntry\DownloadManual\DownloadManualService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Changelog\Infrastructure\Storage\InMemoryManualStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Changelog\Application\ChangelogEntry\ChangelogEntryRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DownloadManualServiceTest extends TestCase
{
    public function testItStreamsTheManualWhenItExistsInStorage(): void
    {
        $entry = ChangelogEntryMother::withManuals();
        $repository = ChangelogEntryRepositoryMother::withEntries($entry);

        $storage = new InMemoryManualStorage();
        $storage->add('changelog-entry.pdf', '%PDF-1.4 test contents');

        $sut = new DownloadManualService($repository, $storage, new ChangelogEntryAuthorizer());

        $response = $sut(new DownloadManualRequest(new ChangelogEntrySlug('changelog-entry')), UserMother::adminUser());

        $this->assertSame('changelog-entry.pdf', $response->fileName);
        $this->assertSame('application/pdf', $response->mimeType);
    }

    public function testEntryWithoutManualFails(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::any());

        $sut = new DownloadManualService($repository, new InMemoryManualStorage(), new ChangelogEntryAuthorizer());

        $this->expectException(ManualNotFoundException::class);

        $sut(new DownloadManualRequest(new ChangelogEntrySlug('changelog-entry')), UserMother::adminUser());
    }

    public function testDeclaredButMissingManualFails(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::withManuals());

        $sut = new DownloadManualService($repository, new InMemoryManualStorage(), new ChangelogEntryAuthorizer());

        $this->expectException(ManualNotFoundException::class);

        $sut(new DownloadManualRequest(new ChangelogEntrySlug('changelog-entry')), UserMother::adminUser());
    }

    public function testAUserCannotDownloadTheManual(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::withManuals());

        $sut = new DownloadManualService($repository, new InMemoryManualStorage(), new ChangelogEntryAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new DownloadManualRequest(new ChangelogEntrySlug('changelog-entry')), UserMother::fromUsername('user@dediez.software'));
    }
}
