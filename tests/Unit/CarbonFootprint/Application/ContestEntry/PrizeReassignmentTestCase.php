<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ContestEntry;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Application\Contest\ContestApplicationTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;

abstract class PrizeReassignmentTestCase extends ContestApplicationTestCase
{
    abstract protected function act(Contest $contest, ContestEntry $entry, User $user): void;

    public function testFirstEntrantsPrizeGoesToTheOldestEligibleWithoutPrize(): void
    {
        $contest = $this->store(ContestMother::publishedFirstEntrants(1, self::ACCOUNT));
        $winner = $this->storeEntry(ContestEntryMother::confirmed($contest, 1, winner: true));
        $disqualified = ContestEntryMother::confirmed($contest, 2);
        $disqualified->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));
        $this->storeEntry($disqualified);
        $fourth = $this->storeEntry(ContestEntryMother::confirmed($contest, 4));
        $third = $this->storeEntry(ContestEntryMother::confirmed($contest, 3));

        $this->act($contest, $winner, $this->owner());

        $this->assertTrue($third->isWinner());
        $this->assertFalse($fourth->isWinner());
        $this->assertFalse($disqualified->isWinner());
        $this->assertSame([(string) $contest->id()], $this->contestLock->acquired());
    }

    public function testFirstEntrantsWithoutEligibleLeavesThePrizeFreeForTheNextConfirmation(): void
    {
        $contest = $this->store(ContestMother::publishedFirstEntrants(1, self::ACCOUNT));
        $winner = $this->storeEntry(ContestEntryMother::confirmed($contest, $contest->countConfirmedEntry(), winner: true));

        $this->act($contest, $winner, $this->owner());

        $this->assertSame(0, $this->contestEntryRepository->countWinners($contest->id()));

        $next = ContestEntryMother::pending($contest, projectContributionId: null);
        $this->confirmer()->confirm($contest, $next, new \DateTimeImmutable('NOW'));

        $this->assertTrue($next->isWinner());
        $this->assertSame(2, $next->entryNumber());
    }

    public function testEveryoneNobodyInherits(): void
    {
        $contest = $this->store(ContestMother::publishedEveryone(self::ACCOUNT));
        $winner = $this->storeEntry(ContestEntryMother::confirmed($contest, 1, winner: true));
        $other = $this->storeEntry(ContestEntryMother::confirmed($contest, 2));

        $this->act($contest, $winner, $this->owner());

        $this->assertFalse($other->isWinner());
    }

    public function testDrawBeforeTheDrawLeavesTheEntryOutOfTheCandidates(): void
    {
        $contest = $this->store(ContestMother::publishedPlatformDraw(1, self::ACCOUNT));
        $entry = $this->storeEntry(ContestEntryMother::confirmed($contest, 1));
        $other = $this->storeEntry(ContestEntryMother::confirmed($contest, 2));

        $this->act($contest, $entry, $this->owner());

        $this->assertSame([(string) $other->id()], array_map('strval', $this->contestEntryRepository->findEligibleWithoutPrizeIds($contest->id())));
        $this->assertSame(0, $contest->redrawnPrizes());
    }

    public function testDrawAlreadyDoneRedrawsASubstitute(): void
    {
        $contest = $this->resolvedPlatformDraw();
        $winner = $this->storeEntry(ContestEntryMother::confirmed($contest, 1, winner: true));
        $substitute = $this->storeEntry(ContestEntryMother::confirmed($contest, 2));

        $this->act($contest, $winner, $this->owner());

        $this->assertTrue($substitute->isWinner());
        $this->assertSame(1, $contest->redrawnPrizes());
    }

    public function testEntryConfirmedAfterTheDrawCanBeTheSubstitute(): void
    {
        $contest = $this->resolvedPlatformDraw();
        $winner = $this->storeEntry(ContestEntryMother::confirmed($contest, $contest->countConfirmedEntry(), winner: true));
        $late = ContestEntryMother::pending($contest, projectContributionId: null);
        $this->confirmer()->confirm($contest, $late, new \DateTimeImmutable('NOW'));

        $this->act($contest, $winner, $this->owner());

        $this->assertTrue($late->isWinner());
    }

    public function testDrawAlreadyDoneWithoutCandidatesHasNoSubstitute(): void
    {
        $contest = $this->resolvedPlatformDraw();
        $winner = $this->storeEntry(ContestEntryMother::confirmed($contest, 1, winner: true));

        $this->act($contest, $winner, $this->owner());

        $this->assertSame(0, $contest->redrawnPrizes());
        $this->assertSame(0, $this->contestEntryRepository->countWinners($contest->id()));
    }

    public function testNonWinnerReassignsNothing(): void
    {
        $contest = $this->store(ContestMother::publishedFirstEntrants(1, self::ACCOUNT));
        $this->storeEntry(ContestEntryMother::confirmed($contest, 1, winner: true));
        $entry = $this->storeEntry(ContestEntryMother::confirmed($contest, 2));
        $other = $this->storeEntry(ContestEntryMother::confirmed($contest, 3));

        $this->act($contest, $entry, $this->owner());

        $this->assertFalse($other->isWinner());
        $this->assertSame(1, $this->contestEntryRepository->countWinners($contest->id()));
    }

    private function resolvedPlatformDraw(): Contest
    {
        $contest = ContestMother::publishedPlatformDraw(1, self::ACCOUNT);
        $contest->close(new \DateTimeImmutable('NOW'));
        $contest->resolveByDraw(UserId::generate(), 1, new \DateTimeImmutable('NOW'));

        return $this->store($contest);
    }
}
