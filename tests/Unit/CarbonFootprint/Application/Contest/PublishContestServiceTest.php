<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\Publish\PublishContestRequest;
use App\CarbonFootprint\Application\Contest\Publish\PublishContestService;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostAlreadyInUseException;
use App\CarbonFootprint\Domain\Model\Contest\ContestPublicationLock;
use App\CarbonFootprint\Domain\Model\Contest\ContestPublished;
use App\CarbonFootprint\Domain\Model\Contest\ContestStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;

class PublishContestServiceTest extends ContestApplicationTestCase
{
    public function testPublishesWithTheDeclaration(): void
    {
        $contest = $this->store(ContestMother::draft(accountId: self::ACCOUNT, hosts: [$this->eventHost()]));
        $owner = $this->owner();

        $this->createSUT()(new PublishContestRequest($contest->id(), responsibilityAccepted: true), $owner);

        $this->assertInstanceOf(ContestPublished::class, $this->spySubscriber->lastEvent());
        $this->assertSame(ContestStatus::Published, $contest->status());
        $this->assertTrue($contest->responsibilityAcceptedBy()?->equals($owner->id()));
        $this->assertSame([self::ACCOUNT], $this->publicationLock->acquired());
    }

    public function testWithoutDeclarationFails(): void
    {
        $contest = $this->store(ContestMother::draft(accountId: self::ACCOUNT, hosts: [$this->eventHost()]));

        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()(new PublishContestRequest($contest->id(), responsibilityAccepted: false), $this->owner());
    }

    public function testCalculatorInAnotherPublishedContestFails(): void
    {
        $host = $this->eventHost(name: 'Congreso anual');
        $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [$host]));
        $contest = $this->store(ContestMother::draft(accountId: self::ACCOUNT, hosts: [$host]));

        $this->expectException(ContestHostAlreadyInUseException::class);
        $this->expectExceptionMessage('Congreso anual');

        $this->createSUT()(new PublishContestRequest($contest->id(), responsibilityAccepted: true), $this->owner());
    }

    public function testCalculatorPublishedConcurrentlyWhileWaitingForTheLockFails(): void
    {
        $host = $this->eventHost(name: 'Congreso anual');
        $contest = $this->store(ContestMother::draft(accountId: self::ACCOUNT, hosts: [$host]));
        $lock = new FakeContestPublicationLock(function () use ($host): void {
            $this->store(ContestMother::published(accountId: self::ACCOUNT, hosts: [$host]));
        });

        $this->expectException(ContestHostAlreadyInUseException::class);
        $this->expectExceptionMessage('Congreso anual');

        $this->createSUT($lock)(new PublishContestRequest($contest->id(), responsibilityAccepted: true), $this->owner());
    }

    public function testOtherAccountIsDenied(): void
    {
        $contest = $this->store(ContestMother::draft(accountId: self::OTHER_ACCOUNT));

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new PublishContestRequest($contest->id(), responsibilityAccepted: true), $this->owner());
    }

    private function createSUT(?ContestPublicationLock $lock = null): PublishContestService
    {
        return new PublishContestService(
            $this->contestRepository,
            $this->authorizer(),
            $this->hostLocator(),
            $lock ?? $this->publicationLock,
            $this->transactionalSession,
        );
    }
}
