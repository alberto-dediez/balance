<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\Draw\DrawContestWinnersRequest;
use App\CarbonFootprint\Application\Contest\Draw\DrawContestWinnersService;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestAlreadyResolvedException;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotEditableException;
use App\CarbonFootprint\Domain\Model\Contest\ContestStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;

class DrawContestWinnersServiceTest extends ContestApplicationTestCase
{
    public function testDrawsWinnersAmongEligibleEntries(): void
    {
        $contest = $this->closedPlatformDraw(2);
        $first = $this->storeEntry(ContestEntryMother::confirmed($contest, 1));
        $second = $this->storeEntry(ContestEntryMother::confirmed($contest, 2));
        $third = $this->storeEntry(ContestEntryMother::confirmed($contest, 3));
        $owner = $this->owner();

        $this->createSUT()(new DrawContestWinnersRequest($contest->id()), $owner);

        $this->assertTrue($first->isWinner());
        $this->assertTrue($second->isWinner());
        $this->assertFalse($third->isWinner());
        $this->assertSame(ContestStatus::Resolved, $contest->status());
        $this->assertSame(3, $contest->drawCandidates());
        $this->assertTrue($contest->drawnBy()?->equals($owner->id()));
        $this->assertSame([(string) $contest->id()], $this->contestLock->acquired());
    }

    public function testMoreWinnersThanEntries(): void
    {
        $contest = $this->closedPlatformDraw(5);
        $entry = $this->storeEntry(ContestEntryMother::confirmed($contest, 1));

        $this->createSUT()(new DrawContestWinnersRequest($contest->id()), $this->owner());

        $this->assertTrue($entry->isWinner());
        $this->assertSame(1, $this->contestEntryRepository->countWinners($contest->id()));
    }

    public function testWithoutEntriesResolvesWithoutWinners(): void
    {
        $contest = $this->closedPlatformDraw(2);

        $this->createSUT()(new DrawContestWinnersRequest($contest->id()), $this->owner());

        $this->assertSame(ContestStatus::Resolved, $contest->status());
        $this->assertSame(0, $contest->drawCandidates());
    }

    public function testIgnoresPendingDisqualifiedAndPurgedEntries(): void
    {
        $contest = $this->closedPlatformDraw(5);
        $pending = $this->storeEntry(ContestEntryMother::pending($contest));
        $disqualified = ContestEntryMother::confirmed($contest, 1);
        $disqualified->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));
        $this->storeEntry($disqualified);
        $purged = ContestEntryMother::confirmed($contest, 2);
        $purged->purgePersonalData(new \DateTimeImmutable('NOW'));
        $this->storeEntry($purged);
        $eligible = $this->storeEntry(ContestEntryMother::confirmed($contest, 3));

        $this->createSUT()(new DrawContestWinnersRequest($contest->id()), $this->owner());

        $this->assertTrue($eligible->isWinner());
        $this->assertFalse($pending->isWinner());
        $this->assertFalse($disqualified->isWinner());
        $this->assertFalse($purged->isWinner());
        $this->assertSame(1, $contest->drawCandidates());
    }

    public function testCannotDrawTwice(): void
    {
        $contest = $this->closedPlatformDraw(1);
        $this->createSUT()(new DrawContestWinnersRequest($contest->id()), $this->owner());

        $this->expectException(ContestAlreadyResolvedException::class);

        $this->createSUT()(new DrawContestWinnersRequest($contest->id()), $this->owner());
    }

    public function testContestMustBeClosed(): void
    {
        $contest = $this->store(ContestMother::publishedPlatformDraw(1, accountId: self::ACCOUNT));

        $this->expectException(ContestNotEditableException::class);

        $this->createSUT()(new DrawContestWinnersRequest($contest->id()), $this->owner());
    }

    public function testOtherAccountIsDenied(): void
    {
        $contest = $this->store(ContestMother::publishedPlatformDraw(1, accountId: self::OTHER_ACCOUNT));
        $contest->close(new \DateTimeImmutable('NOW'));

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new DrawContestWinnersRequest($contest->id()), $this->owner());
    }

    private function closedPlatformDraw(int $winners): Contest
    {
        $contest = ContestMother::publishedPlatformDraw($winners, accountId: self::ACCOUNT);
        $contest->close(new \DateTimeImmutable('NOW'));

        return $this->store($contest);
    }

    private function createSUT(): DrawContestWinnersService
    {
        return new DrawContestWinnersService(
            $this->contestRepository,
            $this->authorizer(),
            $this->contestEntryRepository,
            $this->contestLock,
            $this->winnerPicker,
            $this->transactionalSession,
        );
    }
}
