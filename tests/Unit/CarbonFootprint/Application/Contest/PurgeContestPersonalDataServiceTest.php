<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\PurgePersonalData\PurgeContestPersonalDataRequest;
use App\CarbonFootprint\Application\Contest\PurgePersonalData\PurgeContestPersonalDataService;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotEditableException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;

class PurgeContestPersonalDataServiceTest extends ContestApplicationTestCase
{
    public function testRemovesPendingAndAnonymizesTheRest(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));
        $pending = $this->storeEntry(ContestEntryMother::pending($contest));
        $winner = $this->storeEntry(ContestEntryMother::confirmed($contest, 1, winner: true));
        $disqualified = ContestEntryMother::confirmed($contest, 2);
        $disqualified->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));
        $this->storeEntry($disqualified);
        $contest->close(new \DateTimeImmutable('NOW'));

        $this->createSUT()(new PurgeContestPersonalDataRequest($contest->id()), $this->owner());

        $this->assertNull($this->contestEntryRepository->find($pending->id()));
        $this->assertTrue($winner->isPurged());
        $this->assertTrue($winner->isWinner());
        $this->assertSame(1, $winner->entryNumber());
        $this->assertNull($winner->email());
        $this->assertTrue($disqualified->isPurged());
        $this->assertNull($disqualified->disqualificationReason());
        $this->assertTrue($contest->isPersonalDataPurged());
    }

    public function testPublishedContestCannotBePurged(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));

        $this->expectException(ContestNotEditableException::class);

        $this->createSUT()(new PurgeContestPersonalDataRequest($contest->id()), $this->owner());
    }

    public function testOtherAccountIsDenied(): void
    {
        $contest = $this->store(ContestMother::closed(accountId: self::OTHER_ACCOUNT));

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new PurgeContestPersonalDataRequest($contest->id()), $this->owner());
    }

    private function createSUT(): PurgeContestPersonalDataService
    {
        return new PurgeContestPersonalDataService(
            $this->contestRepository,
            $this->authorizer(),
            $this->contestEntryRepository,
            $this->transactionalSession,
        );
    }
}
