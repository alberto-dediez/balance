<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\Close\CloseContestRequest;
use App\CarbonFootprint\Application\Contest\Close\CloseContestService;
use App\CarbonFootprint\Domain\Model\Contest\ContestStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;

class CloseContestServiceTest extends ContestApplicationTestCase
{
    public function testClosesAndResolvesWithoutPlatformDraw(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));

        $this->createSUT()(new CloseContestRequest($contest->id()), $this->owner());

        $this->assertSame(ContestStatus::Resolved, $contest->status());
    }

    public function testClosesWaitingForThePlatformDraw(): void
    {
        $contest = $this->store(ContestMother::publishedPlatformDraw(2, accountId: self::ACCOUNT));

        $this->createSUT()(new CloseContestRequest($contest->id()), $this->owner());

        $this->assertSame(ContestStatus::Closed, $contest->status());
    }

    public function testUserIsDenied(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new CloseContestRequest($contest->id()), $this->user());
    }

    private function createSUT(): CloseContestService
    {
        return new CloseContestService($this->contestRepository, $this->authorizer());
    }
}
