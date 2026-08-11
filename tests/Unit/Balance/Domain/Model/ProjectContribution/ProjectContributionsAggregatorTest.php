<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregator;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class ProjectContributionsAggregatorTest extends TestCase
{
    private ProjectContributionsAggregator $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->sut = new ProjectContributionsAggregator();
    }

    public function testAggregatesAnEmptySet(): void
    {
        $aggregation = $this->sut->aggregate([]);

        $this->assertSame(0, $aggregation->contributions);
        $this->assertSame(0, $aggregation->paid);
        $this->assertSame(0.0, $aggregation->paidTotalAmount);
        $this->assertSame(0.0, $aggregation->averagePaidAmount);
    }

    public function testCountsEachStatusAndOnlyAddsUpTheMoneyOfThePaidOnes(): void
    {
        $aggregation = $this->sut->aggregate([
            ProjectContributionMother::paid(tonnes: 1.0),
            ProjectContributionMother::paid(tonnes: 3.0),
            ProjectContributionMother::pending(),
            ProjectContributionMother::expired(),
        ]);

        $this->assertSame(4, $aggregation->contributions);
        $this->assertSame(2, $aggregation->paid);
        $this->assertSame(1, $aggregation->pending);
        $this->assertSame(1, $aggregation->expired);
        $this->assertSame(0, $aggregation->failed);
        $this->assertEqualsWithDelta(4.0, $aggregation->paidTonnes, 0.001);
        $this->assertEqualsWithDelta(4000.0, $aggregation->paidCo2eqKg, 0.001);
        $this->assertEqualsWithDelta(96.8, $aggregation->paidTotalAmount, 0.001);
        $this->assertEqualsWithDelta(60.0, $aggregation->paidContributionAmount, 0.001);
        $this->assertEqualsWithDelta(20.0, $aggregation->paidManagementFeeAmount, 0.001);
        $this->assertEqualsWithDelta(48.4, $aggregation->averagePaidAmount, 0.001);
    }
}
