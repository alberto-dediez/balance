<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ContestEntry;

use App\CarbonFootprint\Application\ContestEntry\Confirm\ConfirmContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\Confirm\ConfirmContestEntryService;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Application\Contest\ContestApplicationTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;

class ConfirmContestEntryServiceTest extends ContestApplicationTestCase
{
    public function testNumbersCorrelativelyInPaymentOrderAndAwardsUpToX(): void
    {
        $contest = $this->store(ContestMother::publishedFirstEntrants(2, self::ACCOUNT, ContestParticipationMode::Compensation));
        $a = $this->pending($contest, 'pc-a');
        $b = $this->pending($contest, 'pc-b');
        $c = $this->pending($contest, 'pc-c');

        $this->confirm('pc-b');
        $this->confirm('pc-c');
        $this->confirm('pc-a');

        $this->assertSame([1, 2, 3], [$b->entryNumber(), $c->entryNumber(), $a->entryNumber()]);
        $this->assertTrue($b->isWinner());
        $this->assertTrue($c->isWinner());
        $this->assertFalse($a->isWinner());
        $this->assertTrue($contest->isPublished());
        $this->assertTrue($contest->hasAwardedEveryPrize($this->contestEntryRepository->countWinners($contest->id())));
    }

    public function testEveryoneIsAwarded(): void
    {
        $contest = $this->store(ContestMother::publishedEveryone(self::ACCOUNT, ContestParticipationMode::Compensation));
        $entries = [$this->pending($contest, 'pc-1'), $this->pending($contest, 'pc-2'), $this->pending($contest, 'pc-3')];

        foreach (['pc-1', 'pc-2', 'pc-3'] as $id) {
            $this->confirm($id);
        }

        foreach ($entries as $entry) {
            $this->assertTrue($entry->isWinner());
        }
    }

    public function testDoublePaymentDoesNotDuplicate(): void
    {
        $contest = $this->store(ContestMother::publishedFirstEntrants(5, self::ACCOUNT, ContestParticipationMode::Compensation));
        $entry = $this->pending($contest, 'pc-1');

        $this->confirm('pc-1');
        $this->confirm('pc-1');

        $this->assertSame(1, $entry->entryNumber());
        $this->assertSame(1, $contest->confirmedEntries());
    }

    public function testPaymentWithTheContestClosedStillConfirms(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, participationMode: ContestParticipationMode::Compensation));
        $entry = $this->pending($contest, 'pc-1');
        $contest->close(new \DateTimeImmutable('NOW'));

        $this->confirm('pc-1');

        $this->assertTrue($entry->isConfirmed());
    }

    public function testConfirmedAfterADrawIsCandidateButNotWinner(): void
    {
        $contest = $this->store(ContestMother::publishedPlatformDraw(1, self::ACCOUNT));
        $entry = $this->pending($contest, 'pc-1');
        $contest->close(new \DateTimeImmutable('NOW'));
        $contest->resolveByDraw(UserId::generate(), 0, new \DateTimeImmutable('NOW'));

        $this->confirm('pc-1');

        $this->assertTrue($entry->isConfirmed());
        $this->assertFalse($entry->isWinner());
        $this->assertCount(1, $this->contestEntryRepository->findEligibleWithoutPrizeIds($contest->id()));
    }

    private function pending(Contest $contest, string $projectContributionId): ContestEntry
    {
        return $this->storeEntry(ContestEntryMother::pending($contest, projectContributionId: $projectContributionId));
    }

    private function confirm(string $projectContributionId): void
    {
        (new ConfirmContestEntryService(
            $this->contestRepository,
            $this->contestEntryRepository,
            $this->confirmer(),
            $this->contestLock,
            $this->transactionalSession,
        ))(new ConfirmContestEntryRequest($projectContributionId));
    }
}
