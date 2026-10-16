<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\EconomicSectorRowViewFactory;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivityMother;
use PHPUnit\Framework\TestCase;

class EconomicSectorRowViewFactoryTest extends TestCase
{
    private EconomicSectorRowViewFactory $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->sut = new EconomicSectorRowViewFactory();
    }

    public function testActivityType(): void
    {
        $this->assertSame(ActivityType::EconomicSector, $this->sut->activityType());
    }

    public function testCreatesRowViewFromActivity(): void
    {
        $activity = EconomicSectorActivityMother::registered();

        $row = $this->sut->create($activity);

        $this->assertSame((string) $activity->id(), $row->activityId);
        $this->assertSame((string) $activity->economicSectorId(), $row->itemId);
        $this->assertSame('EUR', $row->unit);
        $this->assertSame(100.0, $row->quantity);
        $this->assertSame($activity->emissions()->co2eq(), $row->co2eq);
        $this->assertNull($row->condition);
    }
}
