<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ContestEntry;

use App\CarbonFootprint\Application\ContestEntry\Disqualify\DisqualifyContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\Disqualify\DisqualifyContestEntryService;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryDisqualified;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;

class DisqualifyContestEntryServiceTest extends PrizeReassignmentTestCase
{
    public function testDisqualifiesWithReasonAndKeepsTheEntry(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $entry = $this->storeEntry(ContestEntryMother::confirmed($contest, 1));
        $owner = $this->owner();

        $this->act($contest, $entry, $owner);

        $this->assertNotNull($this->contestEntryRepository->find($entry->id()));
        $this->assertTrue($entry->isDisqualified());
        $this->assertSame('Datos falsos', $entry->disqualificationReason());
        $this->assertTrue($entry->disqualifiedBy()?->equals($owner->id()));
        $this->assertNotEmpty(array_filter($this->spySubscriber->events(), static fn(object $event): bool => $event instanceof ContestEntryDisqualified));
    }

    public function testUserIsDenied(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $entry = $this->storeEntry(ContestEntryMother::confirmed($contest, 1));

        $this->expectException(UnauthorizedActionException::class);

        $this->act($contest, $entry, $this->user());
    }

    public function testEntryOfAnotherContestIsRejected(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $other = $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [ContestMother::propertyHost()]));
        $entry = $this->storeEntry(ContestEntryMother::confirmed($other, 1));

        $this->expectException(\InvalidArgumentException::class);

        $this->act($contest, $entry, $this->owner());
    }

    protected function act(Contest $contest, ContestEntry $entry, User $user): void
    {
        (new DisqualifyContestEntryService(
            $this->contestRepository,
            $this->contestEntryRepository,
            $this->authorizer(),
            $this->contestLock,
            $this->prizeReassigner(),
            $this->transactionalSession,
        ))(new DisqualifyContestEntryRequest($contest->id(), $entry->id(), 'Datos falsos'), $user);
    }
}
