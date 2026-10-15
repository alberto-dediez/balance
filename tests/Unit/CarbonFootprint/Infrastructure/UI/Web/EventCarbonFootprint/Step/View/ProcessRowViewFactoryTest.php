<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ProcessRowViewFactory;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ProcessActivityMother;
use PHPUnit\Framework\TestCase;

class ProcessRowViewFactoryTest extends TestCase
{
    private ProcessRowViewFactory $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->sut = new ProcessRowViewFactory();
    }

    public function testActivityType(): void
    {
        $this->assertSame(ActivityType::Process, $this->sut->activityType());
    }

    public function testCreatesRowViewFromActivity(): void
    {
        $activity = ProcessActivityMother::registered();

        $row = $this->sut->create($activity);

        $this->assertSame((string) $activity->id(), $row->activityId);
        $this->assertSame((string) $activity->processId(), $row->itemId);
        $this->assertSame('ud', $row->unit);
        $this->assertSame(100.0, $row->quantity);
        $this->assertSame($activity->emissions()->co2eq(), $row->co2eq);
        $this->assertNull($row->condition);
    }
}
