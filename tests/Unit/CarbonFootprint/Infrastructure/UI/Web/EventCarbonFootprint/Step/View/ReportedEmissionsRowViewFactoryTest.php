<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ReportedEmissionsAnswer;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ReportedEmissionsRowViewFactory;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityMother;
use PHPUnit\Framework\TestCase;

class ReportedEmissionsRowViewFactoryTest extends TestCase
{
    private ReportedEmissionsRowViewFactory $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->sut = new ReportedEmissionsRowViewFactory();
    }

    public function testActivityType(): void
    {
        $this->assertSame(ActivityType::ReportedEmissions, $this->sut->activityType());
    }

    public function testCreatesRowViewFromActivity(): void
    {
        $activity = ReportedEmissionsActivityMother::registered();

        $row = $this->sut->create($activity);

        $this->assertSame((string) $activity->id(), $row->activityId);
        $this->assertSame('', $row->itemId);
        $this->assertSame('kg', $row->unit);
        $this->assertSame(3200.0, $row->quantity);
        $this->assertSame($activity->emissions()->co2eq(), $row->co2eq);
        $this->assertSame('Reparto 2025', $row->description);
    }

    public function testCreatesRowViewFromAnswer(): void
    {
        $activityId = ActivityId::generate();

        $row = $this->sut->createFromAnswer(new ReportedEmissionsAnswer('Última milla', new Quantity(0.8, Unit::Tonne), $activityId));

        $this->assertSame((string) $activityId, $row->activityId);
        $this->assertSame('', $row->itemId);
        $this->assertSame('t', $row->unit);
        $this->assertSame(0.8, $row->quantity);
        $this->assertSame('Última milla', $row->description);
    }

    public function testCreatesRowViewFromNewAnswerWithoutActivityId(): void
    {
        $row = $this->sut->createFromAnswer(new ReportedEmissionsAnswer('Reparto', new Quantity(10.0, Unit::Kilogram)));

        $this->assertSame('', $row->activityId);
    }
}
