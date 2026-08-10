<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Fail;

use App\Balance\Application\ProjectContribution\Fail\FailProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Fail\FailProjectContributionService;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use PHPUnit\Framework\TestCase;

class FailProjectContributionServiceTest extends TestCase
{
    public function testFailingAPendingContributionKeepsTheReason(): void
    {
        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::pending();
        $contributions->add($contribution);

        $response = (new FailProjectContributionService($contributions))(
            new FailProjectContributionRequest($contribution->id(), 'card_declined'),
        );

        $this->assertTrue($response->changed);
        $this->assertSame(ProjectContributionStatus::Failed, $response->status);
        $this->assertSame('card_declined', $contribution->failureReason());
        $this->assertNotNull($contribution->resolvedAt());
    }

    public function testAFailedReservationReleasesItsCredit(): void
    {
        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::pending();
        $contributions->add($contribution);

        (new FailProjectContributionService($contributions))(
            new FailProjectContributionRequest($contribution->id(), 'card_declined'),
        );

        $this->assertFalse($contribution->reservesCredit(new \DateTimeImmutable('NOW')));
    }

    public function testAPaidContributionIsNeverFailed(): void
    {
        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::paid();
        $contributions->add($contribution);

        $response = (new FailProjectContributionService($contributions))(
            new FailProjectContributionRequest($contribution->id(), 'card_declined'),
        );

        $this->assertFalse($response->changed);
        $this->assertSame(ProjectContributionStatus::Paid, $response->status);
        $this->assertNull($contribution->failureReason());
    }

    public function testThrowsWhenTheContributionDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new FailProjectContributionService(ProjectContributionRepositoryMother::inMemory()))(
            new FailProjectContributionRequest(ProjectContributionId::generate()),
        );
    }
}
