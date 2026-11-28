<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ContestEntry;

use App\CarbonFootprint\Application\ContestEntry\RemovePending\RemovePendingContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\RemovePending\RemovePendingContestEntryService;
use App\Tests\Unit\CarbonFootprint\Application\Contest\ContestApplicationTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;

class RemovePendingContestEntryServiceTest extends ContestApplicationTestCase
{
    public function testRemovesOnlyPendingEntriesOfTheContribution(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $pending = $this->storeEntry(ContestEntryMother::pending($contest, projectContributionId: 'pc-1'));
        $other = $this->storeEntry(ContestEntryMother::pending($contest, projectContributionId: 'pc-2'));

        $response = (new RemovePendingContestEntryService($this->contestEntryRepository))(new RemovePendingContestEntryRequest('pc-1'));

        $this->assertSame(1, $response->removed);
        $this->assertNull($this->contestEntryRepository->find($pending->id()));
        $this->assertNotNull($this->contestEntryRepository->find($other->id()));
    }

    public function testKeepsConfirmedEntries(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $entry = ContestEntryMother::pending($contest, projectContributionId: 'pc-1');
        $entry->confirm(1, new \DateTimeImmutable('NOW'));
        $this->storeEntry($entry);

        (new RemovePendingContestEntryService($this->contestEntryRepository))(new RemovePendingContestEntryRequest('pc-1'));

        $this->assertNotNull($this->contestEntryRepository->find($entry->id()));
    }
}
