<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\ContestHostLocator;
use App\CarbonFootprint\Application\Contest\View\ViewContestRequest;
use App\CarbonFootprint\Application\Contest\View\ViewContestService;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;

class ViewContestServiceTest extends ContestApplicationTestCase
{
    public function testReturnsHostNames(): void
    {
        $event = $this->eventHost(name: 'Congreso');
        $link = $this->tripLinkHost();
        $missing = ContestMother::propertyHost('missing-property');
        $contest = $this->store(ContestMother::draft(accountId: self::ACCOUNT, hosts: [$event, $link, $missing]));

        $response = $this->createSUT()(new ViewContestRequest($contest->id()), $this->owner());

        $this->assertSame([
            $event->key() => 'Congreso',
            $link->key() => ContestHostLocator::ACCOUNT_TRIP_CALCULATOR_NAME,
            $missing->key() => ContestHostLocator::MISSING_HOST_NAME,
        ], $response->hostNames);
    }

    public function testOtherAccountIsDenied(): void
    {
        $contest = $this->store(ContestMother::draft(accountId: self::OTHER_ACCOUNT));

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new ViewContestRequest($contest->id()), $this->owner());
    }

    private function createSUT(): ViewContestService
    {
        return new ViewContestService($this->contestRepository, $this->authorizer(), $this->hostLocator());
    }
}
