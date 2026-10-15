<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\UseProfileRowViewFactory;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\UseProfileActivityMother;
use PHPUnit\Framework\TestCase;

class UseProfileRowViewFactoryTest extends TestCase
{
    private UseProfileRowViewFactory $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->sut = new UseProfileRowViewFactory();
    }

    public function testActivityType(): void
    {
        $this->assertSame(ActivityType::UseProfile, $this->sut->activityType());
    }

    public function testCreatesRowViewFromActivity(): void
    {
        $activity = UseProfileActivityMother::registered();

        $row = $this->sut->create($activity);

        $this->assertSame((string) $activity->id(), $row->activityId);
        $this->assertSame((string) $activity->useProfileId(), $row->itemId);
        $this->assertSame('ud', $row->unit);
        $this->assertSame(100.0, $row->quantity);
        $this->assertSame($activity->emissions()->co2eq(), $row->co2eq);
        $this->assertNull($row->condition);
    }
}
