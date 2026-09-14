<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry\View;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\View\ViewChangelogEntryRequest;
use App\Changelog\Application\ChangelogEntry\View\ViewChangelogEntryService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Changelog\Application\ChangelogEntry\ChangelogEntryRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ViewChangelogEntryServiceTest extends TestCase
{
    public function testAPublishedEntryIsReturned(): void
    {
        $entry = ChangelogEntryMother::withSlug('mi-entrada');
        $repository = ChangelogEntryRepositoryMother::withEntries($entry);

        $sut = new ViewChangelogEntryService($repository, new ChangelogEntryAuthorizer());

        $response = $sut(new ViewChangelogEntryRequest(new ChangelogEntrySlug('mi-entrada')), UserMother::adminUser());

        $this->assertSame($entry, $response->entry);
    }

    public function testAnUnknownSlugIsNotFound(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries();

        $sut = new ViewChangelogEntryService($repository, new ChangelogEntryAuthorizer());

        $this->expectException(ChangelogEntryNotFoundException::class);

        $sut(new ViewChangelogEntryRequest(new ChangelogEntrySlug('no-existe')), UserMother::adminUser());
    }

    public function testAnUnpublishedEntryIsNotFound(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::unpublished());

        $sut = new ViewChangelogEntryService($repository, new ChangelogEntryAuthorizer());

        $this->expectException(ChangelogEntryNotFoundException::class);

        $sut(new ViewChangelogEntryRequest(new ChangelogEntrySlug('changelog-entry')), UserMother::adminUser());
    }

    public function testAnOwnerCannotViewAnEntry(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::any());

        $sut = new ViewChangelogEntryService($repository, new ChangelogEntryAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ViewChangelogEntryRequest(new ChangelogEntrySlug('changelog-entry')), UserMother::ownerUser());
    }
}
