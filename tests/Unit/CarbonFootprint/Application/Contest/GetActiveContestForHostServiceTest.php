<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\GetActiveForHost\GetActiveContestForHostRequest;
use App\CarbonFootprint\Application\Contest\GetActiveForHost\GetActiveContestForHostService;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;

class GetActiveContestForHostServiceTest extends ContestApplicationTestCase
{
    public function testReturnsThePublishedContestInPeriod(): void
    {
        $host = ContestMother::eventHost('event-a');
        $contest = $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [$host]));

        $response = $this->createSUT()(new GetActiveContestForHostRequest($host));

        $this->assertTrue($response->contest?->id()->equals($contest->id()));
    }

    public function testIgnoresDraftsAndOutOfPeriod(): void
    {
        $this->store(ContestMother::draft(accountId: self::ACCOUNT, hosts: [ContestMother::eventHost('draft')]));
        $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [ContestMother::eventHost('future')], startsAt: new \DateTimeImmutable('+1 day'), endsAt: new \DateTimeImmutable('+2 days')));

        $this->assertNull($this->createSUT()(new GetActiveContestForHostRequest(ContestMother::eventHost('draft')))->contest);
        $this->assertNull($this->createSUT()(new GetActiveContestForHostRequest(ContestMother::eventHost('future')))->contest);
    }

    public function testIgnoresAccountsWithoutTheFeature(): void
    {
        $host = ContestMother::eventHost('event-b');
        $this->store(ContestMother::published(accountId: self::ACCOUNT_WITHOUT_CONTESTS, hosts: [$host]));

        $this->assertNull($this->createSUT()(new GetActiveContestForHostRequest($host))->contest);
    }

    private function createSUT(): GetActiveContestForHostService
    {
        return new GetActiveContestForHostService($this->contestRepository, $this->featuresPolicy());
    }
}
