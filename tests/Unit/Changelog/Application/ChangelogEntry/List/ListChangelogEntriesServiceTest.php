<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry\List;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\List\ListChangelogEntriesRequest;
use App\Changelog\Application\ChangelogEntry\List\ListChangelogEntriesService;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Changelog\Application\ChangelogEntry\ChangelogEntryRepositoryMother;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListChangelogEntriesServiceTest extends TestCase
{
    public function testAnAdminGetsThePublishedEntriesAndTheTotal(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(
            ChangelogEntryMother::withSlug('primera'),
            ChangelogEntryMother::unpublished(),
        );

        $sut = new ListChangelogEntriesService($repository, new ChangelogEntryAuthorizer());

        $response = $sut(new ListChangelogEntriesRequest(), UserMother::adminUser());

        $this->assertCount(1, $response->entries);
        $this->assertSame(1, $response->totalResults);
    }

    public function testItForcesOnlyPublishedEvenIfTheRequestAsksOtherwise(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries(ChangelogEntryMother::unpublished());

        $sut = new ListChangelogEntriesService($repository, new ChangelogEntryAuthorizer());

        $request = new ListChangelogEntriesRequest();
        $request->onlyPublished = false;

        $response = $sut($request, UserMother::adminUser());

        $this->assertCount(0, $response->entries);
    }

    public function testAnOwnerCannotListTheChangelog(): void
    {
        $repository = ChangelogEntryRepositoryMother::withEntries();

        $sut = new ListChangelogEntriesService($repository, new ChangelogEntryAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ListChangelogEntriesRequest(), UserMother::ownerUser());
    }
}
