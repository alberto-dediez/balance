<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\CloseExpired\CloseExpiredContestsRequest;
use App\CarbonFootprint\Application\Contest\CloseExpired\CloseExpiredContestsService;
use App\CarbonFootprint\Domain\Model\Contest\ContestStatus;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

class CloseExpiredContestsServiceTest extends ContestApplicationTestCase
{
    public function testClosesOnlyTheExpiredPublishedContests(): void
    {
        $expired = $this->store(ContestMother::published(hosts: [ContestMother::eventHost('e1')], startsAt: new \DateTimeImmutable('-10 days'), endsAt: new \DateTimeImmutable('-1 hour')));
        $expiredDraw = $this->store(ContestMother::published(hosts: [ContestMother::eventHost('e2')], platformDraw: true, winnersCount: 1, startsAt: new \DateTimeImmutable('-10 days'), endsAt: new \DateTimeImmutable('-1 hour')));
        $running = $this->store(ContestMother::published(hosts: [ContestMother::eventHost('e3')]));

        $response = (new CloseExpiredContestsService($this->contestRepository))(new CloseExpiredContestsRequest(new \DateTimeImmutable('NOW')), UserMother::systemUser());

        $this->assertSame(2, $response->closed);
        $this->assertSame(ContestStatus::Resolved, $expired->status());
        $this->assertSame(ContestStatus::Closed, $expiredDraw->status());
        $this->assertSame(ContestStatus::Published, $running->status());
    }
}
