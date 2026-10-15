<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\CapitalGoodRowViewFactory;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivityMother;
use PHPUnit\Framework\TestCase;

class CapitalGoodRowViewFactoryTest extends TestCase
{
    private CapitalGoodRowViewFactory $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->sut = new CapitalGoodRowViewFactory();
    }

    public function testActivityType(): void
    {
        $this->assertSame(ActivityType::CapitalGood, $this->sut->activityType());
    }

    public function testCreatesRowViewFromActivity(): void
    {
        $activity = CapitalGoodActivityMother::registered(acquisitionYear: 2020);

        $row = $this->sut->create($activity);

        $this->assertSame((string) $activity->id(), $row->activityId);
        $this->assertSame((string) $activity->capitalGoodId(), $row->itemId);
        $this->assertSame('EUR', $row->unit);
        $this->assertSame(100.0, $row->quantity);
        $this->assertSame($activity->emissions()->co2eq(), $row->co2eq);
        $this->assertNull($row->condition);
        $this->assertSame(2020, $row->acquisitionYear);
    }
}
