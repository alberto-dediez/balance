<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Confirm;

use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionService;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionPaymentUnreconciled;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ConfirmProjectContributionServiceTest extends TestCase
{
    public function testConfirmingAPendingContributionPaysIt(): void
    {
        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::pending();
        $contributions->add($contribution);

        $response = (new ConfirmProjectContributionService($contributions))(
            new ConfirmProjectContributionRequest($contribution->id()),
        );

        $this->assertTrue($response->changed);
        $this->assertSame(ProjectContributionStatus::Paid, $response->status);
        $this->assertNotNull($contribution->paidAt());
    }

    public function testAnOverdueReservationIsStillConfirmed(): void
    {
        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::overdue();
        $contributions->add($contribution);

        $response = (new ConfirmProjectContributionService($contributions))(
            new ConfirmProjectContributionRequest($contribution->id()),
        );

        $this->assertTrue($response->changed);
        $this->assertSame(ProjectContributionStatus::Paid, $contribution->status());
    }

    public function testConfirmingTwiceIsANoOp(): void
    {
        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::pending();
        $contributions->add($contribution);

        $sut = new ConfirmProjectContributionService($contributions);

        $sut(new ConfirmProjectContributionRequest($contribution->id()));
        $response = $sut(new ConfirmProjectContributionRequest($contribution->id()));

        $this->assertFalse($response->changed);
        $this->assertSame(ProjectContributionStatus::Paid, $response->status);
    }

    public function testAnExpiredContributionIsNotConfirmedButTheMismatchIsPublished(): void
    {
        DomainEventPublisher::instance()->reset();

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::expired();
        $contributions->add($contribution);

        $spy = new SpyDomainEventSubscriber();

        $response = (new ConfirmProjectContributionService($contributions))(
            new ConfirmProjectContributionRequest($contribution->id()),
        );

        $this->assertFalse($response->changed);
        $this->assertSame(ProjectContributionStatus::Expired, $response->status);
        $this->assertInstanceOf(ProjectContributionPaymentUnreconciled::class, $spy->lastEvent());
    }

    public function testThrowsWhenTheContributionDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ConfirmProjectContributionService(ProjectContributionRepositoryMother::inMemory()))(
            new ConfirmProjectContributionRequest(ProjectContributionId::generate()),
        );
    }
}
