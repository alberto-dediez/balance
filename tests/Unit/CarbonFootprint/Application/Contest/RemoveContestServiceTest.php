<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\Remove\RemoveContestRequest;
use App\CarbonFootprint\Application\Contest\Remove\RemoveContestService;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotRemovableException;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;

class RemoveContestServiceTest extends ContestApplicationTestCase
{
    public function testRemovesADraft(): void
    {
        $contest = $this->store(ContestMother::draft(accountId: self::ACCOUNT));

        $this->createSUT()(new RemoveContestRequest($contest->id()), $this->owner());

        $this->assertNull($this->contestRepository->find($contest->id()));
    }

    public function testPublishedCannotBeRemoved(): void
    {
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT));

        $this->expectException(ContestNotRemovableException::class);

        $this->createSUT()(new RemoveContestRequest($contest->id()), $this->owner());
    }

    public function testOtherAccountIsDenied(): void
    {
        $contest = $this->store(ContestMother::draft(accountId: self::OTHER_ACCOUNT));

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new RemoveContestRequest($contest->id()), $this->owner());
    }

    private function createSUT(): RemoveContestService
    {
        return new RemoveContestService($this->contestRepository, $this->authorizer());
    }
}
