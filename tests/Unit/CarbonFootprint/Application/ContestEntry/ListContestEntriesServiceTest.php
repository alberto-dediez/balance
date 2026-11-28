<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ContestEntry;

use App\CarbonFootprint\Application\ContestEntry\List\ListContestEntriesRequest;
use App\CarbonFootprint\Application\ContestEntry\List\ListContestEntriesService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Application\Contest\ContestApplicationTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;

class ListContestEntriesServiceTest extends ContestApplicationTestCase
{
    public function testListsTheEntriesWithCounters(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $this->storeEntry(ContestEntryMother::confirmed($contest, 1, winner: true));
        $disqualified = ContestEntryMother::confirmed($contest, 2);
        $disqualified->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));
        $this->storeEntry($disqualified);
        $this->storeEntry(ContestEntryMother::pending($contest));

        $response = $this->createSUT()(new ListContestEntriesRequest(contestId: $contest->id()), $this->owner());

        $this->assertSame(3, $response->totalResults);
        $this->assertSame(1, $response->disqualified);
        $this->assertSame(1, $response->winners);
    }

    public function testOtherAccountIsDenied(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::OTHER_ACCOUNT));

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new ListContestEntriesRequest(contestId: $contest->id()), $this->owner());
    }

    private function createSUT(): ListContestEntriesService
    {
        return new ListContestEntriesService($this->contestRepository, $this->contestEntryRepository, $this->authorizer());
    }
}
