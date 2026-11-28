<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ContestEntry;

use App\CarbonFootprint\Application\ContestEntry\Remove\RemoveContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\Remove\RemoveContestEntryService;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;

class RemoveContestEntryServiceTest extends PrizeReassignmentTestCase
{
    public function testRemovesTheEntry(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $entry = $this->storeEntry(ContestEntryMother::confirmed($contest, 1));

        $this->act($contest, $entry, $this->owner());

        $this->assertNull($this->contestEntryRepository->find($entry->id()));
    }

    public function testOtherAccountIsDenied(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::OTHER_ACCOUNT));
        $entry = $this->storeEntry(ContestEntryMother::confirmed($contest, 1));

        $this->expectException(UnauthorizedActionException::class);

        $this->act($contest, $entry, $this->owner());
    }

    public function testAdminWithoutImpersonatingIsDenied(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $entry = $this->storeEntry(ContestEntryMother::confirmed($contest, 1));

        $this->expectException(UnauthorizedActionException::class);

        $this->act($contest, $entry, $this->admin());
    }

    protected function act(Contest $contest, ContestEntry $entry, User $user): void
    {
        (new RemoveContestEntryService(
            $this->contestRepository,
            $this->contestEntryRepository,
            $this->authorizer(),
            $this->contestLock,
            $this->prizeReassigner(),
            $this->transactionalSession,
        ))(new RemoveContestEntryRequest($contest->id(), $entry->id()), $user);
    }
}
